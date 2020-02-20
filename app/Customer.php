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
 * @property string github_username
 */
class Customer extends Model
{
    protected $fillable = [
        "username",
        "email",
        "woo_id",
        "member",
        "first_name",
        "last_name",
        "github_username",
    ];

    protected $casts = [
        "member" => "boolean",
    ];
}
