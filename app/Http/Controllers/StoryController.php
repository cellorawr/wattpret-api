<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Chapter;
use App\Models\LibraryItem;
use App\Models\ReadingProgress;
use App\Models\Story;
use App\Models\Vote;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    private function csv($v): ?string
    {
        if (is_array($v)) return implode(',', array_filter(array_map('trim', $v)));
        return $v;
    }

    private function rules(bool $creating): array
    {
        return [
            'title' => ($creating ? 'required' : 'sometimes|required') . '|string|max:255',
            'synopsis' => 'nullable|string',
            'category' => 'nullable|string',
            'tags' => 'nullable',
            'genres' => 'nullable',
            'lang' => 'nullable|string',
            'type' => 'nullable|string',
            'rights' => 'nullable|string',
            'mature' => 'nullable|boolean',
            'complete' => 'nullable|boolean',
            'published' => 'nullable|boolean',
            'cover_color' => 'nullable|string|max:20',
            'meta' => 'nullable|array',
        ];
    }

    private function clean(array $data): array
    {
        foreach (['tags', 'genres'] as $k) {
            if (array_key_exists($k, $data)) $data[$k] = $this->csv($data[$k]);
        }
        return array_filter($data, fn ($v) => !is_null($v));
    }

    private function base()
    {
        return Story::with('author:id,username,avatar_path')
            ->withCount(['votes', 'chapters' => fn ($q) => $q->where('published', true)]);
    }

    public function index(Request $r)
    {
        $q = $this->base()->where('published', true);

        if ($r->filled('category')) $q->where('category', $r->category);
        if ($r->filled('q')) $q->where('title', 'like', '%' . $r->q . '%');
        if ($r->filled('tag')) $q->where('tags', 'like', '%' . $r->tag . '%');
        if ($r->filled('author')) $q->whereHas('author', fn ($a) => $a->where('username', $r->author));

        if ($r->sort === 'popular') $q->orderByDesc('views');
        elseif ($r->sort === 'votes') $q->orderByDesc('votes_count');
        else $q->latest();

        return $q->paginate($r->integer('per_page', 20));
    }

    public function ranking(Request $r)
    {
        $q = $this->base()->where('published', true);
        if ($r->filled('category')) $q->where('category', $r->category);

        return $q->orderByDesc('votes_count')->orderByDesc('views')->limit(30)->get();
    }

    public function categories()
    {
        return Story::where('published', true)
            ->whereNotNull('category')
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
    }

    public function show(Request $r, Story $story)
    {
        $user = $r->user('sanctum');
        $isOwner = $user && $user->id === $story->user_id;
        abort_if(!$story->published && !$isOwner, 404);

        $story->increment('views');
        $story->load('author:id,username,avatar_path');
        $story->setRelation(
            'chapters',
            $story->chapters()
                ->when(!$isOwner, fn ($q) => $q->where('published', true))
                ->select('id', 'story_id', 'number', 'title', 'published', 'reads', 'created_at', 'updated_at')
                ->get()
        );
        $story->loadCount('votes');

        $data = $story->toArray();
        $data['is_owner'] = $isOwner;
        $data['voted'] = $user ? Vote::where(['user_id' => $user->id, 'story_id' => $story->id])->exists() : false;
        $data['in_library'] = $user ? LibraryItem::where(['user_id' => $user->id, 'story_id' => $story->id])->exists() : false;

        $progress = $user ? ReadingProgress::where(['user_id' => $user->id, 'story_id' => $story->id])->first() : null;
        $data['progress'] = $progress?->progress ?? 0;
        $data['last_chapter_id'] = $progress?->chapter_id;

        return $data;
    }

    public function mine(Request $r)
    {
        return $r->user()->stories()
            ->withCount([
                'votes',
                'chapters',
                'chapters as draft_count' => fn ($q) => $q->where('published', false),
            ])
            ->latest('updated_at')
            ->get();
    }

    public function store(Request $r)
    {
        $data = $this->clean($r->validate($this->rules(true)));
        $data += ['published' => true];

        return response()->json($r->user()->stories()->create($data), 201);
    }

    public function update(Request $r, Story $story)
    {
        abort_if($story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');

        $data = $this->clean($r->validate($this->rules(false)));
        // Boleh dikosongkan:
        if ($r->exists('synopsis')) $data['synopsis'] = $r->input('synopsis');

        $story->update($data);
        return $story->fresh();
    }

    public function destroy(Request $r, Story $story)
    {
        abort_if($story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');
        $story->delete();
        return response()->json(['message' => 'ok']);
    }

    public function storeChapter(Request $r, Story $story)
    {
        abort_if($story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');

        $data = $r->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'published' => 'nullable|boolean',
        ]);

        $number = ($story->chapters()->max('number') ?? 0) + 1;
        $published = $r->boolean('published', true);

        $chapter = $story->chapters()->create([
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'number' => $number,
            'published' => $published,
        ]);

        if ($published) ChapterController::notifyNewChapter($story, $r->user());

        return response()->json($chapter, 201);
    }

    /** PUT /stories/{story}/chapters/order  {ids: [id bab sesuai urutan baru]} */
    public function reorderChapters(Request $r, Story $story)
    {
        abort_if($story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');

        $data = $r->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $own = $story->chapters()->pluck('id')->all();
        $ids = array_values(array_filter($data['ids'], fn ($id) => in_array($id, $own)));

        foreach ($ids as $i => $id) {
            $story->chapters()->where('id', $id)->update(['number' => $i + 1]);
        }

        return response()->json(['message' => 'ok']);
    }

    public function vote(Request $r, Story $story)
    {
        $where = ['user_id' => $r->user()->id, 'story_id' => $story->id];
        $existing = Vote::where($where);
        $voted = !$existing->exists();

        if ($voted) {
            Vote::create($where);
            AppNotification::send(
                $story->user_id, $r->user()->id, 'vote',
                $r->user()->username . ' memberi vote pada "' . $story->title . '"', $story->id
            );
        } else {
            $existing->delete();
        }

        return ['voted' => $voted, 'votes' => $story->votes()->count()];
    }
}
