<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GitHubInviteExpired extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("denhac GitHub invitation expired")
            ->replyTo(config('denhac.access_email'))
            ->line('Your invitation to the denhac GitHub organization has expired.')
            ->line('If you\'d like to retry this, please re-enter your GitHub username on your account page.')
            ->action("Go to My Account", "https://denhac.org/my-account");
    }
}
