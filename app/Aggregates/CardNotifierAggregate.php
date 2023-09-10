<?php

namespace App\Aggregates;

use App\StorableEvents\AccessCards\CardDeactivated;
use App\StorableEvents\AccessCards\CardNotificationEmailNeeded;
use App\StorableEvents\AccessCards\CardNotificationNeeded;
use App\StorableEvents\AccessCards\CardActivated;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class CardNotifierAggregate extends AggregateRoot
{
    private const GLOBAL_UUID = '81baf399-436e-405b-948c-a5a19b751bb3';

    /**
     * @var Collection
     */
    private $cardNotifications;

    public function __construct()
    {
        $this->cardNotifications = collect();
    }

    public static function make()
    {
        // We only have one instance of a card notifier
        return self::retrieve(self::GLOBAL_UUID);
    }

    public function notifyOfCardActivation(CardActivated $event)
    {
        $this->recordThat(new CardNotificationNeeded(
            CardNotificationNeeded::ACTIVATION_TYPE,
            $event->wooCustomerId,
            $event->cardNumber
        ));

        return $this;
    }

    public function notifyOfCardDeactivation(CardDeactivated $event)
    {
        $this->recordThat(new CardNotificationNeeded(
            CardNotificationNeeded::DEACTIVATION_TYPE,
            $event->wooCustomerId,
            $event->cardNumber
        ));

        return $this;
    }

    public function sendNotificationEmail()
    {
        $this->recordThat(new CardNotificationEmailNeeded($this->cardNotifications));

        return $this;
    }

    protected function applyCardNotificationNeeded(CardNotificationNeeded $event)
    {
        $existingNotifications = $this->cardNotifications
            ->filter(function ($notification) use ($event) {
                /* @var CardNotificationNeeded $notification */
                return $notification->wooCustomerId == $event->wooCustomerId &&
                    $notification->cardNumber == $event->cardNumber;
            });

        if ($existingNotifications->count() == 0) {
            $this->cardNotifications->push($event);
        } elseif ($existingNotifications->count() == 1) {
            /** @var CardNotificationNeeded $notification */
            $notification = $existingNotifications->first;

            // If they're different, they cancel out.
            // If not, they're a duplicate and we can ignore the new one
            if ($notification->notificationType != $event->notificationType) {
                $this->cardNotifications->reject(function ($notification) use ($event) {
                    /* @var CardNotificationNeeded $notification */
                    return $notification->wooCustomerId == $event->wooCustomerId &&
                        $notification->cardNumber == $event->cardNumber;
                });
            }
        } else {
            report(new \Exception("Multiple card notifications needed for same woo id/card ({$event->wooCustomerId}/{$event->cardNumber})"));
        }
    }

    protected function applyCardNotificationEmailNeeded(CardNotificationEmailNeeded $event)
    {
        $this->cardNotifications = collect();
    }
}
