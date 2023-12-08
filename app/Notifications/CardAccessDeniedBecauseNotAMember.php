<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardAccessDeniedBecauseNotAMember extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Access denied to denhac')
            ->replyTo(config('denhac.access_email'))
            ->view('emails.card_scan_fail_access_denied', []);
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
