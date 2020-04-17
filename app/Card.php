<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Card
 * @package App
 * @property string number
 * @property boolean active
 * @property boolean member_has_card
 * @property integer woo_customer_id
 */
class Card extends Model
{
    protected $fillable = [
        "number",
        "active",
        "member_has_card",
        "woo_customer_id",
    ];
}
