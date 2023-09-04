<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class UserMembership.
 *
 * @property int id
 * @property int customer_id
 * @property int plan_id
 * @property string status
 */
class UserMembership extends Model
{
    use SoftDeletes;

    public const MEMBERSHIP_FULL_MEMBER = 6410;

    public const MEMBERSHIP_3DP_USER = 8749;

    public const MEMBERSHIP_3DP_TRAINER = 8750;

    public const MEMBERSHIP_LASER_CUTTER_TRAINER = 8761;

    public const MEMBERSHIP_LASER_CUTTER_USER = 8762;

    public const MEMBERSHIP_META_TRAINER = 15914;

    public const MEMBERSHIP_CAN_ID_CHECK = 17682;

    public const MEMBERSHIP_BOARD = 14105;

    public const MEMBERSHIP_OPS_MANAGER = 22820;

    public const MEMBERSHIP_BUSINESS_MANAGER = 22821;

    public const MEMBERSHIP_TREASURER = 22822;

    public const MEMBERSHIP_SAFETY_MANAGER = 22824;

    public const MEMBERSHIP_EVENTS_MANAGER = 22823;

    protected $fillable = [
        'id',
        'plan_id',
        'status',
        'customer_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id');
    }
}
