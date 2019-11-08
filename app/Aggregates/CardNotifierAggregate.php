<?php

namespace App\Aggregates;

use App\StorableEvents\CardActivated;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardNotificationEmailNeeded;
use App\StorableEvents\CardNotificationNeeded;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\AggregateRoot;

final class CardNotifierAggregate extends AggregateRoot
{
    private const GLOBAL_UUID = "81baf399-436e-405b-948c-a5a19b751bb3";
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
        $this->cardNotifications->push($event);
    }

    protected function applyCardNotificationEmailNeeded(CardNotificationEmailNeeded $event)
    {
        $this->cardNotifications = collect();
    }
}
