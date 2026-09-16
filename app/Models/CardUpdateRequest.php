<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CardUpdateRequest.
 *
 * @property string type
 * @property int customer_id
 * @property string card
 * @property Customer customer
 */
class CardUpdateRequest extends Model
{
    public const string ACTIVATION_TYPE = 'enable';

    public const string DEACTIVATION_TYPE = 'disable';

    protected $fillable = [
        'type',
        'customer_id',
        'card',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
