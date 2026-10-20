<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryItem extends Model
{
    protected $table = 'library_items';
    protected $fillable = ['user_id', 'story_id', 'archived', 'offline'];
    protected $casts = ['archived' => 'boolean', 'offline' => 'boolean'];

    public function story() { return $this->belongsTo(Story::class); }
}
