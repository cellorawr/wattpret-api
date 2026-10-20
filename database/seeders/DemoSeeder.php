<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\LibraryItem;
use App\Models\ProfileMessage;
use App\Models\ReadingList;
use App\Models\Story;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

/** Data demo untuk akun "demo" supaya Perpustakaan, Profil, dsb. tidak kosong saat presentasi. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('username', 'demo')->firstOrFail();
        $demo->update(['bio' => 'Akun demo Wattpret.', 'coins' => 50, 'email_verified_at' => now()]);

        $list = ReadingList::firstOrCreate(
            ['user_id' => $demo->id, 'is_default' => true],
            ['name' => "demo's Reading List", 'is_public' => true]
        );

        $authors = User::where('id', '!=', $demo->id)->orderBy('id')->take(6)->get();
        foreach ($authors as $a) {
            $demo->following()->syncWithoutDetaching([$a->id]);
        }

        $stories = Story::where('published', true)->orderBy('id')->take(6)->get();
        foreach ($stories as $i => $s) {
            LibraryItem::firstOrCreate(['user_id' => $demo->id, 'story_id' => $s->id]);
            Vote::firstOrCreate(['user_id' => $demo->id, 'story_id' => $s->id]);
            if ($i < 3) $list->stories()->syncWithoutDetaching([$s->id]);
        }

        $second = ReadingList::firstOrCreate(
            ['user_id' => $demo->id, 'name' => 'Favorit Sekolah'],
            ['is_public' => true, 'is_default' => false]
        );
        $second->stories()->syncWithoutDetaching($stories->skip(3)->pluck('id')->all());

        // Pesan & komentar contoh
        foreach ($authors->take(2) as $a) {
            $m = ProfileMessage::create([
                'profile_user_id' => $demo->id,
                'author_id' => $a->id,
                'body' => 'Hai, mampir ke ceritaku ya, makasih!',
            ]);
            ProfileMessage::create([
                'profile_user_id' => $demo->id,
                'author_id' => $demo->id,
                'parent_id' => $m->id,
                'body' => 'Siap, nanti kubaca!',
            ]);
            AppNotification::send($demo->id, $a->id, 'message', $a->username . ' menulis di profilmu');
        }

        $chapter = Chapter::where('story_id', $stories->first()->id)->orderBy('number')->first();
        if ($chapter) {
            Comment::create(['user_id' => $authors->first()->id, 'chapter_id' => $chapter->id, 'body' => 'Bagus banget bab ini!']);
        }
    }
}
