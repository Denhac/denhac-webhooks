<?php

namespace App\Models;

use Carbon\Carbon;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Class Customer.
 *
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string email
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
 * @property string display_name
 * @property ?string stripe_card_holder_id
 * @property ?int id_was_checked_by_id
 * @property ?Customer idWasCheckedBy
 * @property ?string access_card_temporary_code
 *
 * @method static Builder whereSlackId($slackId)
 */
class Customer extends Model
{
    use Notifiable;
    use SoftDeletes;
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'username',
        'email',
        'member',
        'first_name',
        'last_name',
        'github_username',
        'birthday',
        'slack_id',
        'stripe_card_holder_id',
    ];

    protected $appends = [
        'member_code',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'datetime',
            'member' => 'boolean',
            'id_checked' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function hasMembership($planId): bool
    {
        return $this->memberships->where('plan_id', $planId)->count() > 0;  // TODO Where status is active
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function idWasCheckedBy(): HasOne
    {
        return $this->hasOne(Customer::class, 'id', 'id_was_checked_by_id');
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

    public function equipmentTrainer(): HasManyThrough
    {
        return $this->hasManyThrough(
            TrainableEquipment::class,
            UserMembership::class,
            'customer_id',  // Foreign key on the user memberships table
            'trainer_plan_id', // Foreign key on the trainable equipment table
            'id', // Local key on the customer table
            'plan_id' // Local key on the user membership table
        );
    }

    public function isATrainer()
    {
        return $this->equipmentTrainer()
                ->where('status', 'active')
                ->count() > 0;
    }

    public function routeNotificationForMail(Notification $notification): string
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

        return $hashids->encode($this->id);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(Waiver::class);
    }

    public function hasSignedMembershipWaiver()
    {
        $membershipWaiverTemplateId = Waiver::getValidMembershipWaiverId();

        return $this->waivers()->where('template_id', $membershipWaiverTemplateId)->exists();
    }

    public function getMembershipWaiver()
    {
        $membershipWaiverTemplateId = Waiver::getValidMembershipWaiverId();

        return $this->waivers()->where('template_id', $membershipWaiverTemplateId)->first();
    }

    public function getWaiverUrl()
    {
        $membershipWaiverTemplateId = Waiver::getValidMembershipWaiverId();

        return "https://app.waiverforever.com/pending/{$membershipWaiverTemplateId}?name-first_name-2={$this->first_name}&name-last_name-2={$this->last_name}&email-email-3={$this->email}&checkbox-checked-4=true";
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ?? "$this->first_name $this->last_name"
        );
    }
}
