<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChapterVote extends Model
{
    protected $table = 'chapter_votes';
    protected $fillable = ['user_id', 'chapter_id'];

}
