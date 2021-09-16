<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 */
class TrainableEquipment extends Model
{
    use HasFactory;

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
