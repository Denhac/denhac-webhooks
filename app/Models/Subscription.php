<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string status
 * @property int woo_id
 * @property int customer_id
 * @property Customer customer
 */
class Subscription extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'status',
        'woo_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
