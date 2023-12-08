<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OctoPrintNewUser extends Notification
{
    use Queueable;

    private string $username;

    private string $host;

    private string $password;

    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = setting("hosts.{$this->host}.url");

        return (new MailMessage)
            ->subject("Access to {$this->host} OctoPrint")
            ->line('You have been granted access to an OctoPrint instance!')
            ->line(
                "Your username is \"{$this->username}\" and your password is \"{$this->password}\". ".
                'We recommend logging in and changing your password under your user settings.')
            ->line('Please note that you must be at the space to access this url.')
            ->action('Log in here', $url)
            ->line('Thank you for being a member of denhac!');
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
