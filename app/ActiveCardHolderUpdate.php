<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActiveCardHolderUpdate extends Model
{
    protected $fillable = [
        "card_holders",
    ];

    protected $casts = [
        "card_holders" => "array"
    ];
}
