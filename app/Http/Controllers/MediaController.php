<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private function replace(?string $old, $file, string $dir): string
    {
        if ($old) Storage::disk('public')->delete($old);
        return $file->store($dir, 'public');
    }

    /** Mengirim foto profil milik sendiri (lewat /api agar lolos CORS di Chrome). */
    public function showAvatar(Request $r)
    {
        $path = $r->user()->avatar_path;
        abort_if(!$path || !Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    }

    /** Mengirim gambar latar profil milik sendiri. */
    public function showCover(Request $r)
    {
        $path = $r->user()->cover_path;
        abort_if(!$path || !Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    }

    public function avatar(Request $r)
    {
        $r->validate(['image' => 'required|image|max:4096']);
        $u = $r->user();
        $u->avatar_path = $this->replace($u->avatar_path, $r->file('image'), 'avatars');
        $u->save();
        return $u;
    }

    public function deleteAvatar(Request $r)
    {
        $u = $r->user();
        if ($u->avatar_path) Storage::disk('public')->delete($u->avatar_path);
        $u->avatar_path = null;
        $u->save();
        return $u;
    }

    public function cover(Request $r)
    {
        $r->validate(['image' => 'required|image|max:6144']);
        $u = $r->user();
        $u->cover_path = $this->replace($u->cover_path, $r->file('image'), 'covers');
        $u->save();
        return $u;
    }

    public function deleteCover(Request $r)
    {
        $u = $r->user();
        if ($u->cover_path) Storage::disk('public')->delete($u->cover_path);
        $u->cover_path = null;
        $u->save();
        return $u;
    }

    public function storyCover(Request $r, Story $story)
    {
        abort_if($story->user_id !== $r->user()->id, 403, 'Bukan ceritamu');
        $r->validate(['image' => 'required|image|max:6144']);
        $story->cover_path = $this->replace($story->cover_path, $r->file('image'), 'story-covers');
        $story->save();
        return $story;
    }
}
