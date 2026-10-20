<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Kunci paragraf tempat komentar ditulis ("all" = komentar untuk seluruh bab).
            $table->string('paragraph', 40)->nullable()->after('body')->index();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('paragraph');
        });
    }
};
