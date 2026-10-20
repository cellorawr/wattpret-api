<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\ReadingList;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;

class ReadingListController extends Controller
{
    private function load(ReadingList $list, ?User $viewer = null): ReadingList
    {
        $list->load([
            'owner:id,username,avatar_path',
            'stories' => fn ($q) => $q->with('author:id,username,avatar_path')->withCount(['chapters' => fn ($c) => $c->where('published', true)])->withCount('votes'),
        ])->loadCount(['stories', 'likes']);

        $list->setAttribute('liked', $viewer ? $list->likes()->where('users.id', $viewer->id)->exists() : false);
        return $list;
    }

    private function ensureDefault(User $user): void
    {
        ReadingList::firstOrCreate(
            ['user_id' => $user->id, 'is_default' => true],
            ['name' => $user->username . "'s Reading List", 'is_public' => true]
        );
    }

    private function ownerOrFail(Request $r, ReadingList $list): void
    {
        abort_if($list->user_id !== $r->user()->id, 403, 'Bukan daftar bacaanmu');
    }

    /** GET /reading-lists : daftar milikku */
    public function index(Request $r)
    {
        $user = $r->user();
        $this->ensureDefault($user);

        return $user->readingLists()
            ->with(['stories' => fn ($q) => $q->with('author:id,username,avatar_path')->withCount(['chapters' => fn ($c) => $c->where('published', true)])])
            ->withCount(['stories', 'likes'])
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();
    }

    public function show(Request $r, ReadingList $readingList)
    {
        $viewer = $r->user('sanctum');
        abort_if(!$readingList->is_public && (!$viewer || $viewer->id !== $readingList->user_id), 403, 'Daftar ini pribadi');

        return $this->load($readingList, $viewer);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:100',
            'is_public' => 'nullable|boolean',
        ]);

        $list = ReadingList::create([
            'user_id' => $r->user()->id,
            'name' => $data['name'],
            'is_public' => $r->boolean('is_public', true),
        ]);

        return response()->json($this->load($list, $r->user()), 201);
    }

    public function update(Request $r, ReadingList $readingList)
    {
        $this->ownerOrFail($r, $readingList);
        $r->validate(['name' => 'sometimes|required|string|max:100', 'is_public' => 'nullable|boolean']);

        if ($r->filled('name') && !$readingList->is_default) $readingList->name = $r->name;
        if ($r->exists('is_public')) $readingList->is_public = $r->boolean('is_public');
        $readingList->save();

        return $this->load($readingList, $r->user());
    }

    public function destroy(Request $r, ReadingList $readingList)
    {
        $this->ownerOrFail($r, $readingList);
        abort_if($readingList->is_default, 422, 'Daftar bawaan tidak bisa dihapus');
        $readingList->delete();

        return response()->json(['message' => 'ok']);
    }

    /** POST /reading-lists/{id}/stories/{story} : tambah / keluarkan cerita */
    public function toggleStory(Request $r, ReadingList $readingList, Story $story)
    {
        $this->ownerOrFail($r, $readingList);
        $res = $readingList->stories()->toggle($story->id);

        return ['added' => count($res['attached']) > 0, 'stories_count' => $readingList->stories()->count()];
    }

    /** GET /users/{username}/reading-lists : daftar publik milik pengguna lain */
    public function ofUser(Request $r, string $username)
    {
        $owner = User::where('username', $username)->firstOrFail();
        $viewer = $r->user('sanctum');
        $self = $viewer && $viewer->id === $owner->id;

        if ($self) $this->ensureDefault($owner);

        return $owner->readingLists()
            ->when(!$self, fn ($q) => $q->where('is_public', true))
            ->with(['stories' => fn ($q) => $q->with('author:id,username,avatar_path')->withCount(['chapters' => fn ($c) => $c->where('published', true)])])
            ->withCount(['stories', 'likes'])
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();
    }

    /** POST /reading-lists/{id}/like */
    public function like(Request $r, ReadingList $readingList)
    {
        abort_if(!$readingList->is_public && $readingList->user_id !== $r->user()->id, 403, 'Daftar ini pribadi');

        $res = $readingList->likes()->toggle($r->user()->id);
        $liked = count($res['attached']) > 0;

        if ($liked) {
            AppNotification::send(
                $readingList->user_id, $r->user()->id, 'list_like',
                $r->user()->username . ' menyukai daftar bacaanmu "' . $readingList->name . '"'
            );
        }

        return ['liked' => $liked, 'likes' => $readingList->likes()->count()];
    }

    /** GET /liked-lists : daftar bacaan yang kusukai */
    public function liked(Request $r)
    {
        return ReadingList::whereHas('likes', fn ($q) => $q->where('users.id', $r->user()->id))
            ->where('is_public', true)
            ->with(['owner:id,username,avatar_path', 'stories' => fn ($q) => $q->with('author:id,username,avatar_path')->withCount(['chapters' => fn ($c) => $c->where('published', true)])])
            ->withCount(['stories', 'likes'])
            ->get();
    }
}
