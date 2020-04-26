<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Card
 * @package App
 * @property string number
 * @property boolean active
 * @property boolean member_has_card
 * @property integer woo_customer_id
 * @property Customer customer
 */
class Card extends Model
{
    protected $fillable = [
        "number",
        "active",
        "member_has_card",
        "woo_customer_id",
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'woo_customer_id', 'woo_id');
    }
}
