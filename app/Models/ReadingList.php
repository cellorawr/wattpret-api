<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingList extends Model
{
    protected $table = 'reading_lists';
    protected $fillable = ['user_id', 'name', 'is_public', 'is_default'];
    protected $casts = ['is_public' => 'boolean', 'is_default' => 'boolean'];

    public function owner() { return $this->belongsTo(User::class, 'user_id'); }

    public function stories()
    {
        return $this->belongsToMany(Story::class, 'reading_list_story')->withTimestamps();
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'reading_list_likes')->withTimestamps();
    }
}
