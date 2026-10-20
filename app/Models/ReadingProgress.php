<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    protected $table = 'reading_progress';
    protected $fillable = ['user_id', 'story_id', 'chapter_id', 'progress', 'last_read_at'];
    protected $casts = ['last_read_at' => 'datetime', 'progress' => 'float'];
}
