<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Class MemberBadgedIn
 * @package App\Notifications
 */
class MemberBadgedIn extends Notification implements ShouldQueue
{
    use Queueable;

    private $firstName;
    private $lastName;
    private $scanTime;

    /**
     * Create a new notification instance.
     *
     * @param $firstName
     * @param $lastName
     * @param $scanTime
     */
    public function __construct($firstName, $lastName, $scanTime)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->scanTime = $scanTime;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->content("$this->firstName $this->lastName badged in.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
