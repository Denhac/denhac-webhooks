<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int id
 * @property string name
 * @property int plan_id
 * @property int max_people
 * @property Collection channels
 *
 * @method static Builder wherePlanId($planId)
 */
class VolunteerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_id',
        'max_people',
    ];

    public function channels(): HasMany
    {
        return $this->hasMany(VolunteerGroupChannel::class);
    }
}
