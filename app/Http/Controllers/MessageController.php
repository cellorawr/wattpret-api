<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\ProfileMessage;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** GET /users/{username}/messages : percakapan di profil */
    public function index(string $username)
    {
        $owner = User::where('username', $username)->firstOrFail();

        return ProfileMessage::where('profile_user_id', $owner->id)
            ->whereNull('parent_id')
            ->with(['author:id,username,avatar_path', 'replies.author:id,username,avatar_path'])
            ->latest()
            ->get();
    }

    /** POST /users/{username}/messages  {body, parent_id?} */
    public function store(Request $r, string $username)
    {
        $owner = User::where('username', $username)->firstOrFail();
        $data = $r->validate([
            'body' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:profile_messages,id',
        ]);

        if (!empty($data['parent_id'])) {
            $parent = ProfileMessage::findOrFail($data['parent_id']);
            abort_if($parent->profile_user_id !== $owner->id, 422, 'Pesan induk tidak cocok');
        }

        $msg = ProfileMessage::create([
            'profile_user_id' => $owner->id,
            'author_id' => $r->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        AppNotification::send(
            $owner->id, $r->user()->id, 'message',
            $r->user()->username . ' menulis di profilmu'
        );

        return response()->json($msg->load('author:id,username,avatar_path'), 201);
    }

    /** DELETE /messages/{id} : oleh penulis pesan atau pemilik profil */
    public function destroy(Request $r, ProfileMessage $message)
    {
        $uid = $r->user()->id;
        abort_if($message->author_id !== $uid && $message->profile_user_id !== $uid, 403, 'Tidak diizinkan');

        $message->delete();
        return response()->json(['message' => 'ok']);
    }
}
