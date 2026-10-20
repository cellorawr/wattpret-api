<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ChapterMedia extends Model
{
    protected $table = 'chapter_media';
    protected $fillable = ['chapter_id', 'type', 'path', 'url'];
    protected $hidden = ['path'];
    protected $appends = ['file_url'];

    protected function fileUrl(): Attribute
    {
        return Attribute::get(fn () => $this->path ? asset('storage/' . $this->path) : $this->url);
    }

    public function chapter() { return $this->belongsTo(Chapter::class); }
}
