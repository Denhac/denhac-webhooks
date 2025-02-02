<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int volunteer_group_id
 * @property string type
 * @property string value
 */
class VolunteerGroupChannel extends Model
{
    use HasFactory;

    public const SLACK_CHANNEL_ID = 'slack_channel_id';

    public const SLACK_USERGROUP_ID = 'slack_usergroup_id';

    public const GOOGLE_GROUP_EMAIL = 'google_group_email';

    protected $fillable = [
        'volunteer_group_id',
        'type',
        'value',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(VolunteerGroup::class);
    }
}
