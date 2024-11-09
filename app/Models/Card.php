<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Card.
 *
 * @property string number
 * @property bool active
 * @property bool member_has_card
 * @property int customer_id
 * @property Customer customer
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Card extends Model
{
    protected $fillable = [
        'number',
        'active',
        'member_has_card',
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
