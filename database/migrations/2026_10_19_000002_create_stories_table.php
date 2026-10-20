<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('synopsis')->nullable();
            $table->string('category')->nullable();
            $table->string('tags')->nullable();
            $table->string('cover_color')->default('#FF6122');
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('stories'); }
};
