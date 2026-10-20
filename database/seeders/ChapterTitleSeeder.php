<?php

namespace Database\Seeders;

use App\Models\Chapter;
use Illuminate\Database\Seeder;

/**
 * Mengganti judul bab contoh "[1]. Bab 1", "[2]. Bab 2", ... dengan judul sungguhan.
 * Hanya menyentuh bab yang judulnya masih berpola "[n]. Bab n".
 * Bab buatanmu sendiri tidak diubah.
 *
 * Jalankan: php artisan db:seed --class=ChapterTitleSeeder
 */
class ChapterTitleSeeder extends Seeder
{
    private array $judul = [
        'Pertemuan Pertama', 'Rahasia di Balik Pintu', 'Malam yang Panjang',
        'Janji di Tepi Senja', 'Langkah yang Tertunda', 'Surat yang Tak Terkirim',
        'Bayangan di Jendela', 'Jalan Pulang', 'Hujan Pertama', 'Yang Tak Terucap',
        'Titik Balik', 'Pagi Setelah Badai', 'Pesan Terakhir', 'Di Ujung Cerita',
    ];

    public function run(): void
    {
        $diubah = 0;
        Chapter::where('title', 'like', '[%]. Bab %')->orderBy('id')->each(function ($c) use (&$diubah) {
            if (!preg_match('/^\[(\d+)\]\. Bab \d+$/', $c->title, $m)) return;
            $i = ($c->story_id + (int) $m[1]) % count($this->judul);
            $c->title = '[' . $m[1] . ']. ' . $this->judul[$i];
            $c->save();
            $diubah++;
        });
        $this->command->info("Judul bab diubah: $diubah");
    }
}
