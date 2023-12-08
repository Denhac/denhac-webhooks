<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRequirementsWaiverNeeded extends Notification implements ShouldQueue
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
            ->line('Card Access Revoked - Sign New Waiver')
            ->replyTo(config('denhac.access_email'))
            ->view('emails.new_waiver_needed', [
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
