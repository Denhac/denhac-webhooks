<?php

namespace App\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
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
     *
     * @param $firstName
     * @param $lastName
     * @param $cardNum
     * @param $scanTime
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
    public function via($notifiable)
    {
        return ['mail', 'slack'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \Exception
     */
    public function toMail($notifiable)
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
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return SlackMessage
     * @throws \Exception
     */
    public function toSlack($notifiable)
    {
        $dateTime = new DateTime($this->scanTime);

        return (new SlackMessage)
            ->content(
                "<!channel|channel> $this->firstName $this->lastName is NOT an active member but was able to ".
                "scan in using card $this->cardNum at {$dateTime->format('g:i A')} on ".
                "{$dateTime->format('M d, Y')}. Someone should check on that ASAP!"
            );
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
