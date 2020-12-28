<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ADUpdateRequest
 * @package App
 * @property int id
 * @property string type
 * @property int customer_id
 * @property Customer customer
 */
class ADUpdateRequest extends Model
{
    protected $table = "ad_update_requests";

    public const ACTIVATION_TYPE = 'enable';
    public const DEACTIVATION_TYPE = 'disable';

    public const STATUS_SUCCESS = 'success';

    protected $fillable = [
        'type',
        'customer_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id')->withTrashed();
    }
}
