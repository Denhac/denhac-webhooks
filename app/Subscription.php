<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        "customer_id",
        "status",
        "woo_id"
    ];
}
