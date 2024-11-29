<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string id
 * @property string cardholder_id
 * @property string type
 * @property string status
 */
class StripeCard extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_VIRTUAL = 'virtual';

    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELED = 'canceled';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'cardholder_id',
        'type',
        'status',
    ];
}
