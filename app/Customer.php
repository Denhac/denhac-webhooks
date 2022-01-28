<?php

namespace App;

use Carbon\Carbon;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

/**
 * Class Customer.
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property int woo_id
 * @property string username
 * @property bool member
 * @property string github_username
 * @property string slack_id
 * @property array capabilities
 * @property Carbon birthday
 * @property Collection subscriptions
 * @property Collection cards
 * @property Collection memberships
 * @property Collection equipmentTrainer
 * @property string member_code
 * @method static Builder whereWooId($customerId)
 * @method static Builder whereSlackId($slackId)
 */
class Customer extends Model
{
    use SoftDeletes;
    use Notifiable;

    protected $fillable = [
        'username',
        'email',
        'woo_id',
        'member',
        'first_name',
        'last_name',
        'github_username',
        'birthday',
        'slack_id',
    ];

    protected $appends = [
        'member_code'
    ];

    protected $casts = [
        'member' => 'boolean',
        'capabilities' => 'json',
    ];

    protected $dates = [
        'birthday',
    ];

    public function memberships()
    {
        return $this->hasMany(UserMembership::class, 'customer_id', 'woo_id');
    }

    public function hasMembership($planId)
    {
        return $this->memberships->where('plan_id', $planId)->count() > 0;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'customer_id', 'woo_id');
    }

    public function cards()
    {
        return $this->hasMany(Card::class, 'woo_customer_id', 'woo_id');
    }

    public function hasCapability($capability)
    {
        $capabilities = collect($this->capabilities) ?? collect();

        return $capabilities->has($capability);
    }

    public function isBoardMember(): bool
    {
        return $this->hasCapability('denhac_board_member') ||
            $this->hasMembership(UserMembership::MEMBERSHIP_BOARD);
    }

    public function canIDCheck(): bool
    {
        return $this->hasCapability('denhac_can_verify_member_id') ||
            $this->hasMembership(UserMembership::MEMBERSHIP_CAN_ID_CHECK) ||
            $this->isBoardMember();
    }

    public function equipmentTrainer()
    {
        return $this->hasManyThrough(
            TrainableEquipment::class,
            UserMembership::class,
            'customer_id',  // Foreign key on the user memberships table
            'trainer_plan_id', // Foreign key on the trainable equipment table
            'woo_id', // Local key on the customer table
            'plan_id' // Local key on the user membership table
        );
    }

    public function isATrainer()
    {
        return $this->equipmentTrainer()
                ->where('status', 'active')
                ->count() > 0;
    }

    /**
     * @param Notification $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    public function getMemberCodeAttribute(): string
    {
        $hashids = new Hashids(
            'denhac',
            4,
            '23456789ABCDEFGHKNQRSTUVXZ',
        );

        return $hashids->encode($this->woo_id);
    }
}
