<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** GET /search?q=...&type=title|tag|profile */
    public function index(Request $r)
    {
        $r->validate(['q' => 'required|string|min:1', 'type' => 'nullable|in:title,tag,profile']);
        $q = $r->q;

        if ($r->input('type') === 'profile') {
            return [
                'type' => 'profile',
                'results' => User::where('username', 'like', "%$q%")
                    ->select('id', 'username', 'avatar_path', 'bio')
                    ->withCount('followers')
                    ->limit(30)->get(),
            ];
        }

        $stories = Story::with('author:id,username,avatar_path')
            ->withCount(['votes', 'chapters' => fn ($c) => $c->where('published', true)])
            ->where('published', true);

        $r->input('type') === 'tag'
            ? $stories->where('tags', 'like', "%$q%")
            : $stories->where('title', 'like', "%$q%");

        return [
            'type' => $r->input('type', 'title'),
            'results' => $stories->orderByDesc('views')->limit(30)->get(),
        ];
    }
}
