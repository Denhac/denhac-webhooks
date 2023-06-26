<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string waiver_id
 * @property string template_id
 * @property string template_version
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
    ];
}
