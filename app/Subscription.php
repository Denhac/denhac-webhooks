<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string status
 * @property string woo_id
 * @property string customer_id
 * @property Customer customer
 */
class Subscription extends Model
{
    protected $fillable = [
        'customer_id',
        'status',
        'woo_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id');
    }
}
