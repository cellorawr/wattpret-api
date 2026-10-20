<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('cover_path')->nullable();
            $table->string('lang')->default('Bahasa Indonesia');
            $table->string('type')->nullable();
            $table->string('rights')->nullable();
            $table->boolean('mature')->default(false);
            $table->boolean('complete')->default(false);
            $table->string('genres')->nullable();
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'lang', 'type', 'rights', 'mature', 'complete', 'genres', 'meta']);
        });
    }
};
