<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Chapter;
use App\Models\LibraryItem;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        return AppNotification::where('user_id', $r->user()->id)
            ->with('actor:id,username,avatar_path')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function unreadCount(Request $r)
    {
        return ['unread' => AppNotification::where('user_id', $r->user()->id)->whereNull('read_at')->count()];
    }

    public function read(Request $r, AppNotification $notification)
    {
        abort_if($notification->user_id !== $r->user()->id, 403, 'Tidak diizinkan');
        $notification->update(['read_at' => now()]);
        return $notification;
    }

    public function readAll(Request $r)
    {
        AppNotification::where('user_id', $r->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'ok']);
    }

    /** GET /updates : bab terbaru dari cerita di perpustakaan (tab "Pembaruan") */
    public function updates(Request $r)
    {
        $ids = LibraryItem::where('user_id', $r->user()->id)->where('archived', false)->pluck('story_id');

        return Chapter::whereIn('story_id', $ids)
            ->where('published', true)
            ->with(['story' => fn ($q) => $q->select('id', 'title', 'user_id', 'cover_color', 'cover_path')->with('author:id,username')])
            ->select('id', 'story_id', 'number', 'title', 'created_at')
            ->latest()
            ->limit(50)
            ->get();
    }
}
