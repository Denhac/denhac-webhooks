<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdCheckedWithNoWaiver extends Notification
{
    use Queueable;

    private Customer $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->line("Welcome! We couldn't find your waiver")
            ->replyTo(config('denhac.access_email'))
            ->view('emails.new_member_signup_waiver_needed', [
                'customerWaiverUrl' => $this->customer->getWaiverUrl(),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
