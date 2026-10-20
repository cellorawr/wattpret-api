<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('coins')->default(0);
            $table->date('daily_claimed_at')->nullable();
            $table->string('genres')->nullable();
            $table->string('verification_code', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'cover_path', 'coins', 'daily_claimed_at', 'genres', 'verification_code']);
        });
    }
};
