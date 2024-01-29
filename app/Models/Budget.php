<?php

namespace App\Models;

use App\Actions\QuickBooks\GetAmountSpentByClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string quickbooks_class_id
 * @property string name
 * @property string type
 * @property boolean active
 * @property float allocated_amount
 * @property float currently_used
 * @property string notes
 * @property Customer owner
 *
 * @property float available_to_spend;
 */
class Budget extends Model
{
    public const TYPE_ONE_TIME = 'one-time';
    public const TYPE_RECURRING_MONTHLY = 'recurring-monthly';
    public const TYPE_RECURRING_YEARLY = 'recurring-yearly';
    public const TYPE_POOL = 'pool';

    protected $fillable = [
        'quickbooks_class_id',
        'name',
        'type',
        'active',
        'allocated_amount',
        'currently_used',
        'owner_type',
        'owner_id',
        'notes',
    ];

    protected $appends = [
        'available_to_spend',
    ];

    use HasFactory;

    /**
     * Sync the currently used amount down from QuickBooks
     *
     * @return void
     * @throws \Exception
     */
    public function syncCurrentlyUsed(): void
    {
        $currentlyUsed = $this->currently_used;

        /** @var GetAmountSpentByClass $getAmountSpentByClass */
        $getAmountSpentByClass = app(GetAmountSpentByClass::class);

        $today = Carbon::now();  // TODO I don't think there's a timezone bug here, but still need to check
        switch ($this->type) {
            case self::TYPE_ONE_TIME:
            case self::TYPE_POOL:
                // Date from before we were using quickbooks to catch everything until now
                $startDate = Carbon::createFromDate(2019, 1, 1);
                $endDate = $today;
                break;
            case self::TYPE_RECURRING_MONTHLY:
                $startDate = $today->startOfMonth();
                $endDate = $today->endOfMonth();
                break;
            case self::TYPE_RECURRING_YEARLY:
                $startDate = $today->startOfYear();
                $endDate = $today->endOfYear();
                break;
            default:
                throw new \Exception("Unknown budget type $this->type");
        }

        $quickBooksCurrentlyUsed = $getAmountSpentByClass->execute($this->quickbooks_class_id, $startDate, $endDate);

        if($this->type == self::TYPE_POOL) {
            // For a pool, the "spend" we just fetched is the negative of the amount we have available to use. i.e if
            // the "amount spent" retrieved above is -700.00 then that means our pool has $700.00 it can use. If our
            // allocated amount is $1,000.00 we can consider that $300.00 used. To make the math easier almost
            // everywhere else, we calculate how much we've "used" based on how much is allocated. The only other place
            // we have to care about this is when updating the allocated_amount field.
            $quickBooksCurrentlyUsed = $this->allocated_amount + $quickBooksCurrentlyUsed;
        }

        if (abs($quickBooksCurrentlyUsed - $currentlyUsed) < 0.01) {
            $this->currently_used = $quickBooksCurrentlyUsed;
            $this->save();
            // TODO Trigger any "go update the cards" stuff here?
        }
    }

    protected function allocated_amount(): Attribute
    {
        return Attribute::make(
            get: fn(float $value) => $value,
            set: function(float $value, array $attributes) {
                $result = ['allocated_amount' => $value];

                // In a pool, the amount available to use is constant when the allocated amount changes. To compensate
                // for that, we need to increase the currently used amount if our allocated amount increases and decrease
                // it if the allocated amount decreases.
                if($this->type == self::TYPE_POOL) {
                    $currentAllocated = $attributes['allocated_amount'];

                    $result['currently_used'] = ($value - $currentAllocated) + $currentAllocated;
                }
                return $result;
            }
        );
    }

    /**
     * Returns the available amount that can be spent by the owner of this budget. If the budget is overdrawn, this will
     * return 0. Returning a negative number could potentially affect other budgets when performing a sum of available
     * money to spend across multiple budgets. This also handles a pool that has more money available to it than is
     * allocated.
     *
     * @return float
     */
    public function getAvailableToSpendAttribute(): float
    {
        $canSpend = $this->allocated_amount - $this->available_to_spend;

        // Less than a penny or already over budget? No spend for you!
        if($canSpend < 0.01) {
            return 0;
        }

        // Pools can only spend up to their allocated amount and no more
        if($this->type == self::TYPE_POOL && $canSpend > $this->allocated_amount) {
            return $this->allocated_amount;
        }

        return $canSpend;
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
