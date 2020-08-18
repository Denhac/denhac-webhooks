<?php

namespace App\Aggregates;

use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerCapabilitiesImported;
use App\StorableEvents\CustomerCapabilitiesUpdated;
use App\StorableEvents\CustomerIsNoEventTestUser;
use App\StorableEvents\CustomerRemovedFromBoard;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Spatie\EventSourcing\AggregateRoot;
use Spatie\EventSourcing\ShouldBeStored;

class CapabilityAggregate extends AggregateRoot
{
    private $customerId;
    /**
     * @var Collection
     */
    private $previousCapabilities;
    /**
     * @var Collection
     */
    private $currentCapabilities;

    public $respondToEvents = true;

    public function __construct()
    {
        $this->previousCapabilities = collect();
        $this->currentCapabilities = collect();
    }

    /**
     * @param string $customerId
     * @return CapabilityAggregate
     */
    public static function make(string $customerId): AggregateRoot
    {
        $uuid = Uuid::uuid5(UUID::NAMESPACE_OID, $customerId);
        $aggregateRoot = self::retrieve($uuid);
        $aggregateRoot->customerId = $customerId;

        return $aggregateRoot;
    }

    public function importCapabilities($capabilities)
    {
        if(! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerCapabilitiesImported($this->customerId, $capabilities));

        return $this;
    }

    public function updateCapabilities($capabilities)
    {
        if(! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerCapabilitiesUpdated($this->customerId, $capabilities));

        $this->handleCapabilityUpdates();

        return $this;
    }

    private function handleCapabilityUpdates()
    {
        $this->whenAdded("denhac_board_member", new CustomerBecameBoardMember($this->customerId));
        $this->whenRemoved("denhac_board_member", new CustomerRemovedFromBoard($this->customerId));
    }

    protected function whenAdded($capability, ShouldBeStored $event)
    {
        if (!$this->previousCapabilities->has($capability) &&
            $this->currentCapabilities->has($capability)) {
            $this->recordThat($event);
        }
    }

    protected function whenRemoved($capability, ShouldBeStored $event)
    {
        if ($this->previousCapabilities->has($capability) &&
            !$this->currentCapabilities->has($capability)) {
            $this->recordThat($event);
        }
    }

    protected function applyCustomerIsNoEventTestUser(CustomerIsNoEventTestUser $event)
    {
        $this->respondToEvents = false;
    }

    protected function applyCustomerCapabilitiesImported(CustomerCapabilitiesImported $event)
    {
        $this->currentCapabilities = collect($event->capabilities);
    }

    protected function applyCustomerCapabilitiesUpdated(CustomerCapabilitiesUpdated $event)
    {
        $this->previousCapabilities = $this->currentCapabilities;
        $this->currentCapabilities = collect($event->capabilities);
    }

}
