<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    private const COLS = ['users.id', 'users.username', 'users.avatar_path', 'users.bio'];

    /** GET /following : siapa yang kuikuti */
    public function following(Request $r)
    {
        return $r->user()->following()->select(self::COLS)->withCount('followers')->get();
    }

    /** GET /users/{username} */
    public function profile(Request $r, string $username)
    {
        $user = User::where('username', $username)
            ->select('id', 'username', 'bio', 'avatar_path', 'cover_path', 'created_at')
            ->withCount([
                'followers',
                'following',
                'stories' => fn ($q) => $q->where('published', true),
                'readingLists' => fn ($q) => $q->where('is_public', true),
            ])
            ->firstOrFail();

        $viewer = $r->user('sanctum');
        $data = $user->toArray();
        $data['is_me'] = $viewer && $viewer->id === $user->id;
        $data['is_following'] = $viewer ? $viewer->following()->where('users.id', $user->id)->exists() : false;

        return $data;
    }

    public function followersOf(string $username)
    {
        $u = User::where('username', $username)->firstOrFail();
        return $u->followers()->select(self::COLS)->withCount('followers')->get();
    }

    public function followingOf(string $username)
    {
        $u = User::where('username', $username)->firstOrFail();
        return $u->following()->select(self::COLS)->withCount('followers')->get();
    }

    /** GET /users/suggestions : penulis populer yang belum diikuti */
    public function suggestions(Request $r)
    {
        $me = $r->user();
        $followed = $me->following()->pluck('users.id')->push($me->id);

        return User::whereNotIn('id', $followed)
            ->has('stories')
            ->select('id', 'username', 'avatar_path', 'bio')
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->limit(10)
            ->get();
    }

    /** POST /users/{username}/follow */
    public function toggle(Request $r, string $username)
    {
        $target = User::where('username', $username)->firstOrFail();
        abort_if($target->id === $r->user()->id, 422, 'Tidak bisa mengikuti diri sendiri');

        $changes = $r->user()->following()->toggle($target->id);
        $following = count($changes['attached']) > 0;

        if ($following) {
            AppNotification::send(
                $target->id, $r->user()->id, 'follow',
                $r->user()->username . ' mulai mengikutimu'
            );
        }

        return ['following' => $following, 'followers' => $target->followers()->count()];
    }
}
