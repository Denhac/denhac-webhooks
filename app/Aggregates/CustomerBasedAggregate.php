<?php

namespace App\Aggregates;

use App\Customer;
use Ramsey\Uuid\Uuid;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Spatie\EventSourcing\AggregateRoots\FakeAggregateRoot;
use Tests\Helpers\Wordpress\CustomerBuilder;

trait CustomerBasedAggregate
{
    public $customerId;

    public $respondToEvents = true;

    /**
     * @return MembershipAggregate
     */
    public static function make(string $customerId): AggregateRoot
    {
        $uuid = Uuid::uuid5(UUID::NAMESPACE_OID, $customerId);
        $aggregateRoot = self::retrieve($uuid);
        $aggregateRoot->customerId = $customerId;

        // Some events end up not having a customer id. We don't want those assigned to customer 0.
        if ($customerId == 0) {
            $aggregateRoot->respondToEvents = false;
        }

        return $aggregateRoot;
    }

    /**
     * This method shouldn't be called in production, only in testing when needed.
     *
     * @param  string|CustomerBuilder|Customer  $customer
     * @return $this
     */
    public static function fakeCustomer($customer): FakeAggregateRoot
    {
        if (is_a($customer, CustomerBuilder::class)) {
            $customer = $customer->id;
        } elseif (is_a($customer, Customer::class)) {
            $customer = $customer->woo_id;
        }

        $uuid = Uuid::uuid5(UUID::NAMESPACE_OID, $customer);
        $aggregateRoot = self::retrieve($uuid);
        $aggregateRoot->customerId = $customer;

        return new FakeAggregateRoot($aggregateRoot);
    }
}
