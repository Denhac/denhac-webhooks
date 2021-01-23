<?php

namespace App\Mail;

use App\Customer;
use App\StorableEvents\CardNotificationNeeded;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CardNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * @var Collection
     */
    private $cardNotificationsNeeded;

    /**
     * Create a new message instance.
     *
     * @param $cardNotificationsNeeded
     */
    public function __construct($cardNotificationsNeeded)
    {
        $this->cardNotificationsNeeded = collect($cardNotificationsNeeded);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $customerIds = $this->cardNotificationsNeeded->map(function ($cardNotification) {
            /** @var CardNotificationNeeded|array $cardNotification */
            return is_array($cardNotification) ? $cardNotification['wooCustomerId'] : $cardNotification->wooCustomerId;
        });

        $customers = Customer::whereIn('woo_id', $customerIds)->get();

        $activatedCards = collect();
        $deactivatedCards = collect();

        $this->cardNotificationsNeeded->each(function ($cardNotification) use ($customers, $activatedCards, $deactivatedCards) {
            /** @var CardNotificationNeeded|array $cardNotification */
            $customerId = is_array($cardNotification) ? $cardNotification['wooCustomerId'] : $cardNotification->wooCustomerId;
            $customer = $customers->where('woo_id', $customerId)->first();

            if ($customer == null) {
                return; // TODO Is this an okay solution?
            }

            $notification = [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'card' => $cardNotification['cardNumber'],
            ];

            if ($cardNotification['notificationType'] == CardNotificationNeeded::ACTIVATION_TYPE) {
                $activatedCards->push($notification);
            } elseif ($cardNotification['notificationType'] == CardNotificationNeeded::DEACTIVATION_TYPE) {
                $deactivatedCards->push($notification);
            }
        });

        $date = (new \DateTime())->format('m/d/Y');

        $to_emails = $this->getEmails(config('denhac.notifications.card_notification.to'));
        $cc_emails = $this->getEmails(config('denhac.notifications.card_notification.cc'));

        return $this
            ->subject("Access Card Update {$date}")
            ->view('emails.card_notification')
            ->to($to_emails)
            ->cc($cc_emails)
            ->with([
                'activatedCards' => $activatedCards,
                'deactivatedCards' => $deactivatedCards,
                'updateMessage' => $this->getUpdateMessage($activatedCards, $deactivatedCards),
            ]);
    }

    private function getEmails($emailString) {
        if (empty($emailString)) {
            return null;
        }

        return explode(',', $emailString);
    }

    private function getUpdateMessage(Collection $activatedCards, Collection $deactivatedCards)
    {
        $result = '';
        if ($activatedCards->count() == 0) {
            $result .= 'There were no cards activated';
        } elseif ($activatedCards->count() == 1) {
            $result .= 'There was 1 card activated';
        } else {
            $result .= "There were {$activatedCards->count()} cards activated";
        }

        if ($deactivatedCards->count() == 0) {
            $result .= ' and no cards deactivated';
        } elseif ($deactivatedCards->count() == 1) {
            $result .= ' and 1 card deactivated';
        } else {
            $result .= " and {$deactivatedCards->count()} cards deactivated";
        }

        return $result.' since the last update.';
    }
}
