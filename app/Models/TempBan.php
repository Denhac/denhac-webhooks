<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TempBan
 *
 * @property string user_id
 * @property string channel_id
 * @property ?Carbon expires_at
 * @property ?string reason
 * @property ?string banned_by_id
 */
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
        /** @var Collection $tempBans */
        $tempBans = TempBan::where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->get();

        return $tempBans
            ->filter(function ($tempBan) {
                /** @var TempBan $tempBan */
                return is_null($tempBan->expires_at) || $tempBan->expires_at >= Carbon::now();
            })
            ->count() > 0;
    }
}
