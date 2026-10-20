<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh diambil dari lib/data.dart (aplikasi Flutter).
 * Setiap penulis dibuat sebagai user (password: password123).
 */
class StorySeeder extends Seeder
{
    private array $stories = [
        // [judul, penulis, kategori, tag, jumlah bab, views]
        ['Jurnal Anak Kos Pertama', 'dinda.menulis', 'Fiksi Remaja', 'remaja,kos,persahabatan', 44, 1800000],
        ['Geng Atap Sekolah', 'bumiaksara', 'Fiksi Remaja', 'sekolah,geng,remaja', 38, 1200000],
        ['Bintang di Kelas Sebelah', 'salsabila_r', 'Fiksi Remaja', 'sekolah,cinta,remaja', 51, 2100000],
        ['Seragam Putih Abu-abu', 'ketikanrani', 'Fiksi Remaja', 'sma,sahabat,remaja', 29, 900000],
        ['Dunia Setelah Konser', 'mimpiseribu', 'Fiksi Penggemar', 'fandom,musik,fiksipenggemar', 36, 1500000],
        ['Catatan Fandom Kita', 'ara.ketik', 'Fiksi Penggemar', 'fandom,komunitas,teman', 25, 820000],
        ['Sahabat Sang Idola', 'lintangmalam', 'Fiksi Penggemar', 'idola,persahabatan,au', 41, 1100000],
        ['Tiket Terakhir', 'fiksiwulan', 'Fiksi Penggemar', 'konser,fandom,au', 22, 640000],
        ['Kerajaan di Balik Kabut', 'penjagabuku', 'Fantasi', 'kerajaan,sihir,petualangan', 58, 2400000],
        ['Penjaga Lentera Terakhir', 'senjakala_', 'Fantasi', 'lentera,sihir,takdir', 47, 1600000],
        ['Naga Kecil dari Gunung Arum', 'tintabiru', 'Fantasi', 'naga,petualangan,keluarga', 33, 1300000],
        ['Sihir di Kota Bawah Laut', 'ombakpagi', 'Fantasi', 'laut,sihir,misteri', 40, 980000],
        ['Operasi Fajar', 'garudaputra', 'Aksi', 'agen,misi,aksi', 45, 1900000],
        ['Kurir Tengah Malam', 'rafi_nugraha', 'Aksi', 'kota,kejar,aksi', 39, 1400000],
        ['Garis Merah Pelabuhan', 'dermagabaja', 'Aksi', 'pelabuhan,aksi,persahabatan', 31, 870000],
        ['Pelari dari Zona Tujuh', 'zonanol', 'Aksi', 'distopia,aksi,bertahan', 43, 1100000],
        ['Rumah Nomor Tujuh', 'jejakkata', 'Misteri', 'rumah,teka-teki,misteri', 49, 2200000],
        ['Kunci yang Hilang di Perpustakaan', 'rakbuku', 'Misteri', 'perpustakaan,detektif,sekolah', 34, 1300000],
        ['Jejak di Kebun Teh', 'hijaupagi', 'Misteri', 'kebun,detektif,keluarga', 37, 960000],
        ['Surat Tanpa Nama', 'anonimabu', 'Misteri', 'surat,rahasia,misteri', 42, 1700000],
        ['Lima Menit Sebelum Gelap', 'detikmalam', 'Thriller', 'tegang,waktu,thriller', 46, 2000000],
        ['Saksi Terakhir', 'bayangtipis', 'Thriller', 'saksi,kota,thriller', 35, 1200000],
        ['Pesan dari Nomor Asing', 'sinyalhilang', 'Thriller', 'pesan,misteri,thriller', 40, 1600000],
        ['Lift Lantai Tiga Belas', 'gedungtua', 'Thriller', 'gedung,tegang,thriller', 28, 890000],
        ['Penunggu Sekolah Tua', 'malamjumat', 'Horor', 'sekolah,hantu,horor', 52, 2600000],
        ['Boneka di Loteng', 'senyapdesa', 'Horor', 'boneka,rumah,horor', 33, 1400000],
        ['Jalan Pulang yang Salah', 'kabutmalam', 'Horor', 'perjalanan,mistis,horor', 30, 1000000],
        ['Kos Lantai Dua', 'lampuredup', 'Horor', 'kos,mistis,horor', 41, 1800000],
        ['Emak vs Grup WhatsApp', 'ketawaterus', 'Humor', 'komedi,keluarga,humor', 48, 2300000],
        ['Anak Rantau Kena Mental', 'rantauceria', 'Humor', 'rantau,komedi,humor', 37, 1700000],
        ['Tetangga Absurd', 'gangsempit', 'Humor', 'tetangga,komedi,humor', 32, 1100000],
        ['Kisah Kucing Bos Kantor', 'meongkerja', 'Humor', 'kantor,kucing,humor', 39, 1500000],
        ['Hari Ketika Hujan Berhenti', 'rintikpagi', 'Acak', 'acak,hujan,kehidupan', 27, 1300000],
        ['Catatan di Balik Jendela', 'jendelakata', 'Acak', 'acak,catatan,harian', 21, 760000],
        ['Rumah di Ujung Gang', 'gangbuntu', 'Acak', 'acak,rumah,keluarga', 30, 1000000],
        ['Surat dari Stasiun Kecil', 'stasiunsenja', 'Cerita Pendek', 'cerpen,surat,rindu', 3, 540000],
        ['Payung Biru', 'hujanbulan', 'Cerita Pendek', 'cerpen,hujan,persahabatan', 2, 430000],
        ['Kopi Terakhir Ayah', 'seduhkata', 'Cerita Pendek', 'cerpen,keluarga,haru', 4, 820000],
        ['Pulang ke Kampung Halaman', 'tanahkelahiran', 'Fiksi Umum', 'keluarga,kampung,kehidupan', 36, 1600000],
        ['Warung Bu Tinah', 'dapurkata', 'Fiksi Umum', 'warung,tetangga,kehidupan', 33, 1100000],
        ['Anak Sulung', 'kakaktertua', 'Fiksi Umum', 'keluarga,tanggungjawab,haru', 42, 1900000],
        ['Bos Baru di Lantai Lima', 'kantorkata', 'ChickLit', 'kantor,komedi,chicklit', 44, 2000000],
        ['Resep Cinta Chef Amel', 'dapurhati', 'ChickLit', 'masak,cinta,chicklit', 38, 1400000],
        ['Mulai dari Nol Lagi', 'ketikmanis', 'ChickLit', 'karier,cinta,chicklit', 35, 1200000],
        ['Langkah Menuju Subuh', 'hijrahpelan', 'Spiritual', 'hijrah,doa,spiritual', 34, 1500000],
        ['Titipan Ibu', 'doaibu', 'Spiritual', 'keluarga,doa,spiritual', 29, 1100000],
        ['Senja di Serambi Masjid', 'serambikata', 'Spiritual', 'masjid,teman,spiritual', 31, 930000],
        ['Surat dari Tanah Jawa', 'babadkata', 'Fiksi Sejarah', 'sejarah,kerajaan,perjuangan', 39, 1200000],
        ['Pelabuhan Sunda Kelapa', 'layarlama', 'Fiksi Sejarah', 'pelabuhan,dagang,sejarah', 32, 860000],
        ['Kereta Terakhir 1945', 'rodabesi', 'Fiksi Sejarah', 'kemerdekaan,kereta,sejarah', 37, 1000000],
        ['Belajar Menulis dari Nol', 'pelatihpena', 'Nonfiksi', 'menulis,tips,nonfiksi', 25, 1400000],
        ['Catatan Anak Rantau', 'rantaujujur', 'Nonfiksi', 'rantau,pengalaman,nonfiksi', 28, 980000],
        ['Kebiasaan Kecil, Hidup Besar', 'harikecil', 'Nonfiksi', 'kebiasaan,motivasi,nonfiksi', 30, 1700000],
        ['Koloni di Bulan Biru', 'astronotkata', 'Fiksi Ilmiah', 'luar angkasa,koloni,fiksiilmiah', 46, 1800000],
        ['Kode Terakhir', 'robotpuisi', 'Fiksi Ilmiah', 'robot,masa depan,fiksiilmiah', 36, 1200000],
        ['Kota di Atas Awan', 'langitlipat', 'Fiksi Ilmiah', 'kota,teknologi,fiksiilmiah', 33, 990000],
        ['Peta di Balik Lemari', 'penjelajahcilik', 'Petualangan', 'peta,harta,petualangan', 41, 1700000],
        ['Menyusuri Sungai Barito', 'arusderas', 'Petualangan', 'sungai,hutan,petualangan', 35, 1000000],
        ['Ekspedisi Gunung Sunyi', 'pendakikata', 'Petualangan', 'gunung,teman,petualangan', 38, 1300000],
        ['Tamu Malam di Rumah Tua', 'bulanmerah', 'Vampir', 'vampir,rumah,fantasi', 40, 1500000],
        ['Perpustakaan Abadi', 'abadikata', 'Vampir', 'vampir,buku,misteri', 32, 880000],
        ['Senja Tak Pernah Usai', 'senjagelap', 'Vampir', 'vampir,kota,fantasi', 36, 1100000],
        ['Bulan Purnama di Desa Kabut', 'auman', 'Manusia Serigala', 'serigala,desa,fantasi', 43, 1900000],
        ['Kawanan Terakhir', 'rimbaraya', 'Manusia Serigala', 'serigala,kawanan,petualangan', 37, 1200000],
        ['Penjaga Hutan Utara', 'taringperak', 'Manusia Serigala', 'serigala,hutan,persahabatan', 30, 940000],
        ['Kamera Tua Nenek', 'lensagaib', 'Paranormal', 'misteri,kamera,paranormal', 38, 1600000],
        ['Radio yang Menyala Sendiri', 'gelombangsunyi', 'Paranormal', 'radio,rumah,paranormal', 31, 1000000],
        ['Murid Baru Kelas Sebelah', 'bisikmalam', 'Paranormal', 'sekolah,misteri,paranormal', 35, 1400000],
        ['Kumpulan Rindu Sederhana', 'sajakpagi', 'Puisi', 'puisi,rindu,sajak', 40, 720000],
        ['Hujan dalam Bait', 'baitsenja', 'Puisi', 'puisi,hujan,sajak', 35, 650000],
        ['Surat untuk Diri Sendiri', 'kataperlahan', 'Puisi', 'puisi,semangat,sajak', 28, 910000],
        ['Siti di Tepi Telaga', 'hikayatlama', 'Klasik', 'klasik,desa,hikayat', 30, 1100000],
        ['Salah Pilih Jalan', 'balaipustaka', 'Klasik', 'klasik,keluarga,hikayat', 27, 860000],
        ['Layar Terkembang di Pantai', 'kisahdulu', 'Klasik', 'klasik,laut,hikayat', 33, 980000],
        ['My Devil HUSBAND', 'demimoy', 'Romansa', 'lovestory', 60, 24500000],
        ['Silencio', 'Winnyraca', 'Romansa', 'love', 40, 1470000],
        ['Bening dan Banyu', 'MommieXYZ', 'Romansa', 'romantis', 50, 5560000],
        ['Jan... Serigala Bulan', 'SKIA LIN', 'Romansa', 'romansa', 45, 3170000],
        ['Unconditional Love', 'apinath', 'Romansa', 'romansa', 40, 2000000],
        ['BEGIN Again', 'Rhainike', 'Romansa', 'romansa', 30, 1000000],
        ['Unwanted Marriage', 'Huzaifah Shafia', 'Romansa', 'romansa', 35, 900000],
        ['Unexpected Groom', 'Huzaifah Shafia', 'Romansa', 'romansa', 33, 700000],
        ['Senja di Ujung Dermaga', 'nadiraputri', 'Fiksi Remaja', 'remaja,persahabatan,sekolah', 62, 3000000],
        ['Kopi Pertama di Bulan Mei', 'kirana_w', 'Fiksi Remaja', 'romansa,kampus,ringan', 48, 3000000],
        ['Kelas 12 IPA 3', 'raflisetia', 'Fiksi Remaja', 'sekolah,remaja,komedi', 47, 2000000],
        ['Balik Kanan Jatuh Cinta', 'zyourwrite', 'Fiksi Remaja', 'ceo,chicklit,cinta', 55, 2000000],
        ['Surat untuk Esok Hari', 'aya.writes', 'Fiksi Remaja', 'keluarga,inspiratif,usai', 57, 3000000],
    ];

    private array $colors = ['#1B2433', '#B9B7C9', '#F4F1EA', '#1C1C1C', '#8E7BB5', '#E6D9EC', '#BFD6EA', '#8A9A86', '#F8F1E4', '#FFD84D', '#6F7B66', '#FF6122'];

    public function run(): void
    {
        $authors = [];
        foreach ($this->stories as $i => [$title, $author, $category, $tags, $parts, $views]) {
            $authors[$author] ??= User::create([
                'name' => $author,
                'username' => $author,
                'email' => $author . '@wattpret.test',
                'password' => 'password123',
                'bio' => 'Penulis di Wattpret.',
            ]);

            $story = Story::create([
                'user_id' => $authors[$author]->id,
                'title' => $title,
                'synopsis' => 'Sinopsis singkat untuk "' . $title . '". Ikuti kisahnya bab demi bab.',
                'category' => $category,
                'tags' => $tags,
                'cover_color' => $this->colors[$i % count($this->colors)],
                'views' => $views,
                'published' => true,
            ]);

            // Maksimal 8 bab contoh per cerita supaya seeding cepat.
            for ($n = 1; $n <= min($parts, 8); $n++) {
                Chapter::create([
                    'story_id' => $story->id,
                    'number' => $n,
                    'title' => $n === 1 ? 'Prolog' : "[" . ($n - 1) . "]. Bab " . ($n - 1),
                    'body' => $this->lorem($title, $n),
                    'published' => true,
                ]);
            }
        }

        // Akun demo untuk login
        User::create([
            'name' => 'demo',
            'username' => 'demo',
            'email' => 'demo@wattpret.test',
            'password' => 'password123',
        ]);
    }

    private function lorem(string $title, int $n): string
    {
        $p = "Pagi itu udara terasa lebih dingin dari biasanya. Ia melangkah pelan menyusuri jalan yang sudah dihafalnya, sambil memikirkan semua hal yang belum sempat dikatakan.";
        $q = "Di kejauhan terdengar suara orang-orang memulai hari. Ada rasa ragu, tetapi juga harapan kecil yang tidak mau padam.";
        return "$title - Bab $n\n\n$p\n\n$q\n\n$p\n\n$q\n\n$p";
    }
}
