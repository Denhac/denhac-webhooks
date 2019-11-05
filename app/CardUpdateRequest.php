<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CardUpdateRequest
 * @package App
 * @property string type
 * @property integer customerId
 */
class CardUpdateRequest extends Model
{
    public const ACTIVATION_TYPE = "enable";
    public const DEACTIVATION_TYPE = "disable";

    protected $fillable = [
        "type",
        "customer_id",
        "card",
    ];
}
