<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Customer
 * @package App
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property integer woo_id
 * @property string username
 * @property boolean member
 */
class Customer extends Model
{
    protected $fillable = [
        "username",
        "email",
        "woo_id",
        "member",
        "first_name",
        "last_name"
    ];

    protected $casts = [
        "member" => "boolean",
    ];
}
