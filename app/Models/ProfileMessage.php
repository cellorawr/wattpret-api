<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileMessage extends Model
{
    protected $table = 'profile_messages';
    protected $fillable = ['profile_user_id', 'author_id', 'parent_id', 'body'];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function replies() { return $this->hasMany(ProfileMessage::class, 'parent_id')->oldest(); }
}
