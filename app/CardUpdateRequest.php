<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CardUpdateRequest.
 * @property string type
 * @property int customer_id
 * @property string card
 * @property Customer customer
 */
class CardUpdateRequest extends Model
{
    public const ACTIVATION_TYPE = 'enable';
    public const DEACTIVATION_TYPE = 'disable';

    public const STATUS_NOT_DONE = 'not_done';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR_MULTIPLE_CARD_HOLDERS = 'error_multiple_card_holders';
    public const STATUS_DEACTIVATE_CARD_NOT_FOUND = 'deactivate_card_not_found';

    protected $fillable = [
        'type',
        'customer_id',
        'card',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id');
    }
}
