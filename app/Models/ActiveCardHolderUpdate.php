<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ActiveCardHolderUpdate.
 *
 * @property array card_holders
 */
class ActiveCardHolderUpdate extends Model
{
    protected $fillable = [
        'card_holders',
    ];

    protected $casts = [
        'card_holders' => 'array',
    ];
}
