<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaypalBasedMember extends Model
{
    protected $fillable = [
        'paypal_id',
    ];
}
