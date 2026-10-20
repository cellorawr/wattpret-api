<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Chapter;
use App\Models\ChapterMedia;
use App\Models\ChapterRevision;
use App\Models\ChapterVote;
use App\Models\Comment;
use App\Models\LibraryItem;
use App\Models\ReadingProgress;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChapterController extends Controller
{
    /** Beri tahu pengikut penulis + pembaca yang menyimpan cerita ini. */
    public static function notifyNewChapter(Story $story, User $author): void
    {
        $ids = $author->followers()->pluck('users.id')
            ->merge(LibraryItem::where('story_id', $story->id)->where('archived', false)->pluck('user_id'))
            ->unique();

        foreach ($ids as $uid) {
            AppNotification::send(
                (int) $uid, $author->id, 'chapter',
                $story->title . ' diperbarui dengan bab baru', $story->id
            );
        }
    }

    private function ownerOrFail(Request $r, Chapter $chapter): void
    {
        abort_if($chapter->story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');
    }

    public function show(Request $r, Chapter $chapter)
    {
        $user = $r->user('sanctum');
        $story = $chapter->story;
        $isOwner = $user && $user->id === $story->user_id;
        abort_if((!$chapter->published || !$story->published) && !$isOwner, 404);

        $chapter->increment('reads');

        $visible = fn ($q) => $q->when(!$isOwner, fn ($x) => $x->where('published', true));
        $prev = $visible(Chapter::where('story_id', $story->id)->where('number', '<', $chapter->number))
            ->orderByDesc('number')->value('id');
        $next = $visible(Chapter::where('story_id', $story->id)->where('number', '>', $chapter->number))
            ->orderBy('number')->value('id');

        $data = $chapter->load('media')->toArray();
        $data['story'] = ['id' => $story->id, 'title' => $story->title, 'user_id' => $story->user_id];
        $data['prev_id'] = $prev;
        $data['next_id'] = $next;
        $data['votes_count'] = $chapter->votes()->count();
        $data['comments_count'] = $chapter->comments()->count();
        $data['voted'] = $user ? ChapterVote::where(['user_id' => $user->id, 'chapter_id' => $chapter->id])->exists() : false;

        if ($user) {
            ReadingProgress::updateOrCreate(
                ['user_id' => $user->id, 'story_id' => $story->id],
                ['chapter_id' => $chapter->id, 'last_read_at' => now()]
            );
        }

        return $data;
    }

    public function update(Request $r, Chapter $chapter)
    {
        $this->ownerOrFail($r, $chapter);
        $r->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'nullable|string',
            'published' => 'nullable|boolean',
        ]);

        $newTitle = $r->filled('title') ? $r->title : $chapter->title;
        $newBody = $r->exists('body') ? ($r->input('body') ?? '') : $chapter->body;

        // Simpan riwayat revisi bila isi berubah.
        if ($newTitle !== $chapter->title || $newBody !== $chapter->body) {
            ChapterRevision::create([
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'body' => $chapter->body,
            ]);
        }

        $wasPublished = $chapter->published;
        $chapter->title = $newTitle;
        $chapter->body = $newBody;
        if ($r->exists('published')) $chapter->published = $r->boolean('published');
        $chapter->save();

        if (!$wasPublished && $chapter->published) {
            ChapterController::notifyNewChapter($chapter->story, $r->user());
        }

        return $chapter->fresh('media');
    }

    public function destroy(Request $r, Chapter $chapter)
    {
        $this->ownerOrFail($r, $chapter);
        foreach ($chapter->media as $m) {
            if ($m->path) Storage::disk('public')->delete($m->path);
        }
        $chapter->delete();
        return response()->json(['message' => 'ok']);
    }

    public function revisions(Request $r, Chapter $chapter)
    {
        $this->ownerOrFail($r, $chapter);
        return $chapter->revisions()->limit(50)->get();
    }

    public function vote(Request $r, Chapter $chapter)
    {
        $where = ['user_id' => $r->user()->id, 'chapter_id' => $chapter->id];
        $existing = ChapterVote::where($where);
        $voted = !$existing->exists();
        $voted ? ChapterVote::create($where) : $existing->delete();

        return ['voted' => $voted, 'votes' => $chapter->votes()->count()];
    }

    // ------------------------------------------------------------ komentar
    public function comments(Request $r, Chapter $chapter)
    {
        $q = Comment::where('chapter_id', $chapter->id)->whereNull('parent_id');

        // Komentar per paragraf: ?paragraph=3 (atau "all" untuk komentar bab).
        if ($r->filled('paragraph')) {
            $p = (string) $r->query('paragraph');
            $q->where(fn ($w) => $p === 'all'
                ? $w->where('paragraph', 'all')->orWhereNull('paragraph')
                : $w->where('paragraph', $p));
        }

        return $q->with(['user:id,username,avatar_path', 'replies.user:id,username,avatar_path'])
            ->latest()
            ->paginate(100);
    }

    /** GET /chapters/{id}/comment-counts : jumlah komentar per paragraf. */
    public function commentCounts(Chapter $chapter)
    {
        $rows = Comment::where('chapter_id', $chapter->id)
            ->selectRaw("COALESCE(paragraph, 'all') as p, COUNT(*) as n")
            ->groupBy('p')
            ->get();

        $out = [];
        foreach ($rows as $row) $out[$row->p] = (int) $row->n;

        return response()->json((object) $out);
    }

    public function storeComment(Request $r, Chapter $chapter)
    {
        $data = $r->validate([
            'body' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'paragraph' => 'nullable|string|max:40',
        ]);

        if (!empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);
            abort_if($parent->chapter_id !== $chapter->id, 422, 'Komentar induk tidak cocok');
        }

        $comment = Comment::create([
            'user_id' => $r->user()->id,
            'chapter_id' => $chapter->id,
            'parent_id' => $data['parent_id'] ?? null,
            'paragraph' => $data['paragraph'] ?? null,
            'body' => $data['body'],
        ]);

        $story = $chapter->story;
        AppNotification::send(
            $story->user_id, $r->user()->id, 'comment',
            $r->user()->username . ' berkomentar di "' . $story->title . '"', $story->id
        );

        return response()->json($comment->load('user:id,username,avatar_path'), 201);
    }

    public function destroyComment(Request $r, Comment $comment)
    {
        $isAuthor = $comment->user_id === $r->user()->id;
        $isStoryOwner = $comment->chapter->story->user_id === $r->user()->id;
        abort_if(!$isAuthor && !$isStoryOwner, 403, 'Tidak diizinkan');

        $comment->delete();
        return response()->json(['message' => 'ok']);
    }

    // --------------------------------------------------------------- media
    public function storeMedia(Request $r, Chapter $chapter)
    {
        $this->ownerOrFail($r, $chapter);
        $r->validate(['type' => 'required|in:image,video']);

        if ($r->type === 'image') {
            $r->validate(['image' => 'required|image|max:5120']);
            $media = ChapterMedia::create([
                'chapter_id' => $chapter->id,
                'type' => 'image',
                'path' => $r->file('image')->store('chapter-media', 'public'),
            ]);
        } else {
            $r->validate(['url' => 'required|url|max:500']);
            $media = ChapterMedia::create([
                'chapter_id' => $chapter->id,
                'type' => 'video',
                'url' => $r->url,
            ]);
        }

        return response()->json($media, 201);
    }

    /** GET /media/{media}/file : mengirim gambar bab lewat /api (lolos CORS di Chrome). */
    public function showMedia(ChapterMedia $media)
    {
        abort_if($media->type !== 'image' || !$media->path || !Storage::disk('public')->exists($media->path), 404);
        return Storage::disk('public')->response($media->path);
    }

    public function destroyMedia(Request $r, ChapterMedia $media)
    {
        $this->ownerOrFail($r, $media->chapter);
        if ($media->path) Storage::disk('public')->delete($media->path);
        $media->delete();
        return response()->json(['message' => 'ok']);
    }
}
