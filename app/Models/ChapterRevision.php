<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChapterRevision extends Model
{
    protected $table = 'chapter_revisions';
    protected $fillable = ['chapter_id', 'title', 'body'];

}
