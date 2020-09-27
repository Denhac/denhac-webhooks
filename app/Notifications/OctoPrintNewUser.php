<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OctoPrintNewUser extends Notification
{
    use Queueable;

    private $username;
    private $host;
    private $password;

    /**
     * @param $username
     * @param $host
     * @param $password
     */
    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = setting("hosts.{$this->host}.url");

        return (new MailMessage)
            ->subject("Access to {$this->host} OctoPrint")
            ->line('You have been granted access to an OctoPrint instance!')
            ->line(
                "Your username is \"{$this->username}\" and your password is \"{$this->password}\". " .
                "We recommend logging in and changing your password under your user settings.")
            ->line("Please note that you must be at the space to access this url.")
            ->action('Log in here', $url)
            ->line('Thank you for being a member of denhac!');
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
