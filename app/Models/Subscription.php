<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int id
 * @property string status
 * @property int customer_id
 * @property Customer customer
 */
class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'customer_id',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
