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
    public const ACTIVATION_TYPE = 'enable';

    public const DEACTIVATION_TYPE = 'disable';

    public const UPDATE_NAME_TYPE = 'update_name';

    public const STATUS_NOT_DONE = 'not_done';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR_MULTIPLE_CARD_HOLDERS = 'error_multiple_card_holders';

    public const STATUS_DEACTIVATE_CARD_NOT_FOUND = 'deactivate_card_not_found';

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
