<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
