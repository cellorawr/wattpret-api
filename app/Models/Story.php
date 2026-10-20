<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    protected $fillable = [
        'user_id', 'title', 'synopsis', 'category', 'tags', 'cover_color', 'cover_path',
        'lang', 'type', 'rights', 'mature', 'complete', 'genres', 'meta', 'views', 'published',
    ];

    protected $casts = [
        'published' => 'boolean',
        'mature' => 'boolean',
        'complete' => 'boolean',
        'meta' => 'array',
    ];

    protected $hidden = ['cover_path'];
    protected $appends = ['cover_url'];

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_path ? asset('storage/' . $this->cover_path) : null);
    }

    public function author() { return $this->belongsTo(User::class, 'user_id'); }
    public function chapters() { return $this->hasMany(Chapter::class)->orderBy('number'); }
    public function votes() { return $this->hasMany(Vote::class); }
}
