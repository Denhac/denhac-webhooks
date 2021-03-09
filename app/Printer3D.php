<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Printer3D
 * @package App
 * @property int id
 * @property string name
 * @property string status
 * @property Carbon status_updated_at
 */
class Printer3D extends Model
{
    protected $table = "3dprinters";

    protected $fillable = [
        'name',
        'status',
        'status_updated_at',
    ];

    public $casts = [
        'status_updated_at' => 'datetime',
    ];

    const STATUS_PRINT_STARTED = "print-started";
    const STATUS_PRINT_DONE = "print-done";
    const STATUS_PRINT_FAILED = "print-failed";
    const STATUS_PRINT_PAUSED = "print-paused";
    const STATUS_ERROR = "error";
    const STATUS_USER_ACTION_NEEDED = "user-action-needed";
    const STATUS_UNKNOWN = "unknown";

    public static function getStatus($topic): string
    {
        if(in_array($topic, ["Print Started"])) {
            return self::STATUS_PRINT_STARTED;
        }
        if(in_array($topic, ["Print Done"])) {
            return self::STATUS_PRINT_DONE;
        }
        if(in_array($topic, ["Print Failed"])) {
            return self::STATUS_PRINT_FAILED;
        }
        if(in_array($topic, ["Print Paused"])) {
            return self::STATUS_PRINT_PAUSED;
        }
        if(in_array($topic, ["Error"])) {
            return self::STATUS_ERROR;
        }
        if(in_array($topic, ["User Action Needed"])) {
            return self::STATUS_USER_ACTION_NEEDED;
        }

        return self::STATUS_UNKNOWN;
    }
}
