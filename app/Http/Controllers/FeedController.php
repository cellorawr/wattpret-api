<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    /**
     * GET /feed : aktivitas terbaru dari orang yang kuikuti
     * (cerita baru dan bab baru), diurutkan dari yang terbaru.
     */
    public function index(Request $r)
    {
        $ids = $r->user()->following()->pluck('users.id');

        $stories = Story::whereIn('user_id', $ids)
            ->where('published', true)
            ->with('author:id,username')
            ->withCount(['votes', 'chapters' => fn ($q) => $q->where('published', true)])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($s) => [
                'type' => 'story',
                'at' => $s->created_at,
                'story' => $s,
            ]);

        $chapters = Chapter::where('published', true)
            ->whereHas('story', fn ($q) => $q->whereIn('user_id', $ids)->where('published', true))
            ->with(['story' => fn ($q) => $q->select('id', 'title', 'user_id', 'cover_color', 'cover_path', 'views', 'category', 'tags')
                ->with('author:id,username')])
            ->select('id', 'story_id', 'number', 'title', 'created_at')
            ->latest()
            ->limit(30)
            ->get()
            ->filter(fn ($c) => $c->number > 1)
            ->map(fn ($c) => [
                'type' => 'chapter',
                'at' => $c->created_at,
                'chapter' => ['id' => $c->id, 'number' => $c->number, 'title' => $c->title],
                'story' => $c->story,
            ]);

        return $stories->concat($chapters)
            ->sortByDesc('at')
            ->take(40)
            ->values();
    }
}
