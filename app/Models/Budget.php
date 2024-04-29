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
    use HasFactory;

    public const TYPE_ONE_TIME = 'one-time';
    public const TYPE_RECURRING_MONTHLY = 'recurring-monthly';
    public const TYPE_RECURRING_YEARLY = 'recurring-yearly';
    public const TYPE_POOL = 'pool';

    private const BUDGET_EXTRA_SETTINGS_KEY = 'budget.extra';

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

    public static function getExtraBufferAmount(): float
    {
        return setting(self::BUDGET_EXTRA_SETTINGS_KEY, 10);
    }

    public static function setExtraBufferAmount(float $bufferAmount): void
    {
        setting([
            self::BUDGET_EXTRA_SETTINGS_KEY => $bufferAmount,
        ])->save();
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
        $canSpend = $this->allocated_amount - $this->currently_used;

        // Less than a penny or already over budget? No spend for you!
        if($canSpend < 0.01) {
            return 0;
        }

        if($this->type == self::TYPE_POOL) {
            // Pools can only spend up to their allocated amount and no more
            if ($canSpend > $this->allocated_amount) {
                return $this->allocated_amount;
            }

            // They also get no extra buffer amount
            return $canSpend;
        }

        return $canSpend + self::getExtraBufferAmount();
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
