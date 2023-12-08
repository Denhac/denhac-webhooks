<?php

namespace App\Notifications;

use DateTime;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardAccessAllowedButNotAMemberRedAlert extends Notification
{
    use Queueable;

    private string $firstName;

    private string $lastName;

    private string $scanTime;

    private string $cardNum;

    public function __construct($firstName, $lastName, $cardNum, $scanTime)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->cardNum = $cardNum;
        $this->scanTime = $scanTime;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @throws Exception
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[important] !ALERT! Non-member badged into the space')
            ->replyTo(config('denhac.access_email'))
            ->view('emails.card_scan_fail_not_a_member', [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'cardNum' => $this->cardNum,
                'dateTime' => new DateTime($this->scanTime),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
