<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $fillable = ['user_id', 'actor_id', 'type', 'message', 'story_id', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    /** Kirim notifikasi (dilewati jika pengirim = penerima). */
    public static function send(int $userId, ?int $actorId, string $type, string $message, ?int $storyId = null): void
    {
        if ($actorId !== null && $userId === $actorId) return;

        static::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'message' => $message,
            'story_id' => $storyId,
        ]);
    }
}
