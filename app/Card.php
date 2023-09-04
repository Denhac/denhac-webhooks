<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Card.
 *
 * @property string number
 * @property bool active
 * @property bool member_has_card
 * @property int woo_customer_id
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
        'woo_customer_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'woo_customer_id', 'woo_id');
    }
}
