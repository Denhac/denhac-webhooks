<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        "username",
        "email",
        "woo_id",
        "member"
    ];
}
