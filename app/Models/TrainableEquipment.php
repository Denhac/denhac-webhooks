<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int id
 * @property string name
 * @property int user_plan_id
 * @property int trainer_plan_id
 */
class TrainableEquipment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'user_plan_id',
        'user_slack_id',
        'user_email',
        'trainer_plan_id',
        'trainer_slack_id',
        'trainer_email',
    ];
}
