<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'username', 'bio', 'password',
        'avatar_path', 'cover_path', 'coins', 'daily_claimed_at', 'genres', 'verification_code',
    ];

    protected $hidden = ['password', 'remember_token', 'verification_code', 'avatar_path', 'cover_path'];

    protected $appends = ['avatar_url', 'cover_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'daily_claimed_at' => 'date',
            'password' => 'hashed',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path ? asset('storage/' . $this->avatar_path) : null);
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_path ? asset('storage/' . $this->cover_path) : null);
    }

    public function stories() { return $this->hasMany(Story::class); }
    public function following() { return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id'); }
    public function followers() { return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id'); }
    public function readingLists() { return $this->hasMany(ReadingList::class); }
}
