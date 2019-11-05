<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CardUpdateRequest
 * @package App
 * @property string type
 * @property integer customer_id
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, "customer_id", "woo_id");
    }
}
