<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id', 'chapter_id', 'parent_id', 'body', 'paragraph'];

    public function user() { return $this->belongsTo(User::class); }
    public function chapter() { return $this->belongsTo(Chapter::class); }
    public function replies() { return $this->hasMany(Comment::class, 'parent_id')->oldest(); }
}
