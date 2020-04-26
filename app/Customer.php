<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

/**
 * Class Customer.
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property int woo_id
 * @property string username
 * @property bool member
 * @property string github_username
 * @property string slack_id
 * @property array capabilities
 */
class Customer extends Model
{
    use Notifiable;

    protected $fillable = [
        'username',
        'email',
        'woo_id',
        'member',
        'first_name',
        'last_name',
        'github_username',
    ];

    protected $casts = [
        'member' => 'boolean',
        'capabilities' => 'json',
    ];

    public function hasCapability($capability)
    {
        $capabilities = collect($this->capabilities) ?? collect();

        return $capabilities->has($capability);
    }

    /**
     * @param Notification $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}
