<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempBan extends Model
{
    protected $fillable = [
        'user_id',
        'channel_id',
        'expires_at',
        'reason',
        'banned_by_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function isBanned($userId, $channelId): bool
    {
        return TempBan::where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->count() > 0;
    }
}
