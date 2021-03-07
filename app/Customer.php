<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

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

    public function isBoardMember()
    {
        return $this->hasCapability('denhac_board_member');
    }

    /**
     * @param Notification $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}
