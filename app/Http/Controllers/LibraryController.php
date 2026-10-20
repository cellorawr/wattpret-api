<?php

namespace App\Http\Controllers;

use App\Models\LibraryItem;
use App\Models\ReadingProgress;
use App\Models\Story;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /** GET /library?archived=0|1&sort=recent|added|title */
    public function index(Request $r)
    {
        $uid = $r->user()->id;

        $items = LibraryItem::where('user_id', $uid)
            ->where('archived', $r->boolean('archived'))
            ->with(['story' => fn ($q) => $q
                ->with('author:id,username,avatar_path')
                ->withCount(['votes', 'chapters' => fn ($c) => $c->where('published', true)])])
            ->get();

        $progress = ReadingProgress::where('user_id', $uid)->get()->keyBy('story_id');

        $rows = $items->filter(fn ($i) => $i->story)->map(function ($i) use ($progress) {
            $p = $progress->get($i->story_id);
            return array_merge($i->story->toArray(), [
                'archived' => $i->archived,
                'offline' => $i->offline,
                'added_at' => $i->created_at,
                'progress' => $p?->progress ?? 0,
                'last_chapter_id' => $p?->chapter_id,
                'last_read_at' => $p?->last_read_at,
            ]);
        });

        $sorted = match ($r->input('sort', 'recent')) {
            'title' => $rows->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            'added' => $rows->sortByDesc('added_at'),
            default => $rows->sortByDesc(fn ($x) => $x['last_read_at'] ?? $x['added_at']),
        };

        return $sorted->values();
    }

    /** POST /library/{story} : tambah / hapus dari perpustakaan */
    public function toggle(Request $r, Story $story)
    {
        $where = ['user_id' => $r->user()->id, 'story_id' => $story->id];
        $item = LibraryItem::where($where);
        $added = !$item->exists();
        $added ? LibraryItem::create($where) : $item->delete();

        return ['in_library' => $added];
    }

    /** POST /library/{story}/archive : arsipkan / kembalikan */
    public function archive(Request $r, Story $story)
    {
        $item = LibraryItem::where(['user_id' => $r->user()->id, 'story_id' => $story->id])->firstOrFail();
        $item->update(['archived' => !$item->archived]);

        return ['archived' => $item->archived];
    }

    /** POST /library/{story}/offline : tandai unduhan offline */
    public function offline(Request $r, Story $story)
    {
        $item = LibraryItem::where(['user_id' => $r->user()->id, 'story_id' => $story->id])->firstOrFail();
        $item->update(['offline' => !$item->offline]);

        return ['offline' => $item->offline];
    }

    /** GET /progress : {story_id: progress} */
    public function progress(Request $r)
    {
        return ReadingProgress::where('user_id', $r->user()->id)
            ->get(['story_id', 'chapter_id', 'progress', 'last_read_at']);
    }

    /** PUT /stories/{story}/progress */
    public function setProgress(Request $r, Story $story)
    {
        $data = $r->validate([
            'progress' => 'required|numeric|min:0|max:1',
            'chapter_id' => 'nullable|integer|exists:chapters,id',
        ]);

        $p = ReadingProgress::updateOrCreate(
            ['user_id' => $r->user()->id, 'story_id' => $story->id],
            [
                'progress' => $data['progress'],
                'chapter_id' => $data['chapter_id'] ?? null,
                'last_read_at' => now(),
            ]
        );

        return $p;
    }
}
