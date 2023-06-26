<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string waiver_id
 * @property string template_id
 * @property string template_version
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property int customer_id
 * @property Customer customer
 */
class Waiver extends Model
{
    protected $fillable = [
        'waiver_id',
        'template_id',
        'template_version',
        'status',
        'email',
        'first_name',
        'last_name',
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'woo_id');
    }
}
