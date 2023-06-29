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
 * @property Carbon birthday
 * @property Collection subscriptions
 * @property Collection cards
 * @property Collection memberships
 * @property Collection equipmentTrainer
 * @property string member_code
 * @property bool id_checked
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
        'id_checked' => 'boolean',
    ];

    protected $dates = [
        'birthday',
    ];

    public function memberships()
    {
        return $this->hasMany(UserMembership::class, 'customer_id', 'woo_id');
    }

    public function hasMembership($planId): bool
    {
        return $this->memberships->where('plan_id', $planId)->count() > 0;  // TODO Where status is active
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'customer_id', 'woo_id');
    }

    public function cards()
    {
        return $this->hasMany(Card::class, 'woo_customer_id', 'woo_id');
    }

    public function isABoardMember(): bool
    {
        return $this->hasMembership(UserMembership::MEMBERSHIP_BOARD);
    }

    public function isAManager(): bool
    {
        return $this->hasMembership(UserMembership::MEMBERSHIP_OPS_MANAGER) ||
            $this->hasMembership(UserMembership::MEMBERSHIP_BUSINESS_MANAGER) ||
            $this->hasMembership(UserMembership::MEMBERSHIP_TREASURER) ||
            $this->hasMembership(UserMembership::MEMBERSHIP_SAFETY_MANAGER) ||
            $this->hasMembership(UserMembership::MEMBERSHIP_EVENTS_MANAGER);
    }

    public function canIDCheck(): bool
    {
        return $this->hasMembership(UserMembership::MEMBERSHIP_CAN_ID_CHECK) ||
            $this->isAManager() ||
            $this->isABoardMember();
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

    public function waivers()
    {
        return $this->hasMany(Waiver::class, 'customer_id', 'woo_id');
    }

    public function hasSignedMembershipWaiver()
    {
        $membershipWaiverTemplateId = config('denhac.waiver.membership_waiver_template_id');

        return $this->waivers()->where('template_id', $membershipWaiverTemplateId)->exists();
    }

    public function getWaiverUrl()
    {
        $membershipWaiverTemplateId = config('denhac.waiver.membership_waiver_template_id');

        return "https://app.waiverforever.com/pending/{$membershipWaiverTemplateId}/?name-first_name-2={$this->first_name}&name-last_name-2={$this->last_name}&email-email-3={$this->email}&checkbox-checked-4=true";
    }
}
