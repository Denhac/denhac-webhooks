<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Customer.
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property int woo_id
 * @property string username
 * @property bool member
 * @property string github_username
 * @property string slack_id
 * @property array capabilities
 */
class Customer extends Model
{
    protected $fillable = [
        'username',
        'email',
        'woo_id',
        'member',
        'first_name',
        'last_name',
        'github_username',
    ];

    protected $casts = [
        'member' => 'boolean',
    ];
}
