<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserMembership
 * @package App
 * @property int id
 * @property int customer_id
 * @property int plan_id
 * @property string status
 */
class UserMembership extends Model
{
    public const MEMBERSHIP_3DP_USER = 8749;
    public const MEMBERSHIP_3DP_TRAINER = 8750;

    protected $fillable = [
        'id',
        'plan_id',
        'status',
        'customer_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id');
    }
}
