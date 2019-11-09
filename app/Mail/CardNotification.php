<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CardNotification extends Mailable
{
    use Queueable, SerializesModels;
    private $cardNotifications;

    /**
     * Create a new message instance.
     *
     * @param $cardNotifications
     */
    public function __construct($cardNotifications)
    {

        $this->cardNotifications = $cardNotifications;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->view('emails.card_notification')
            ->with([
                "cardNotifications" => $this->cardNotifications,
            ]);
    }
}
