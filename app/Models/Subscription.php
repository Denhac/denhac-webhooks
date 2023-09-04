<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string status
 * @property int woo_id
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
