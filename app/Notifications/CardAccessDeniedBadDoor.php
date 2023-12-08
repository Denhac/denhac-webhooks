<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardAccessDeniedBadDoor extends Notification
{
    use Queueable;

    public function __construct()
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Card scan not working')
            ->replyTo(config('denhac.access_email'))
            ->view('emails.card_scan_fail_bad_door', []);
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
