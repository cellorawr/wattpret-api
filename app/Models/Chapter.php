<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = ['story_id', 'number', 'title', 'body', 'published', 'reads'];
    protected $casts = ['published' => 'boolean'];

    public function story() { return $this->belongsTo(Story::class); }
    public function media() { return $this->hasMany(ChapterMedia::class); }
    public function votes() { return $this->hasMany(ChapterVote::class); }
    public function comments() { return $this->hasMany(Comment::class); }
    public function revisions() { return $this->hasMany(ChapterRevision::class)->latest(); }
}
