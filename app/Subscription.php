<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string status
 * @property string wooId
 * @property string customerId
 */
class Subscription extends Model
{
    protected $fillable = [
        "customer_id",
        "status",
        "woo_id"
    ];
}
