<?php

namespace App\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardAccessAllowedButNotAMemberRedAlert extends Notification
{
    use Queueable;

    private $firstName;

    private $lastName;

    private $scanTime;

    private $cardNum;

    /**
     * Create a new notification instance.
     */
    public function __construct($firstName, $lastName, $cardNum, $scanTime)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->cardNum = $cardNum;
        $this->scanTime = $scanTime;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     *
     * @throws \Exception
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

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
