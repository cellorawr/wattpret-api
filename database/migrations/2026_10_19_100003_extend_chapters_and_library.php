<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->unsignedBigInteger('reads')->default(0);
        });
        Schema::table('library_items', function (Blueprint $table) {
            $table->boolean('archived')->default(false);
            $table->boolean('offline')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('chapters', fn (Blueprint $t) => $t->dropColumn('reads'));
        Schema::table('library_items', fn (Blueprint $t) => $t->dropColumn(['archived', 'offline']));
    }
};
