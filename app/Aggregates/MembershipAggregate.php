<?php

namespace App\Aggregates;

use App\Aggregates\MembershipTraits\ActiveDirectory;
use App\Aggregates\MembershipTraits\Cards;
use App\Aggregates\MembershipTraits\Github;
use App\Aggregates\MembershipTraits\Subscription;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerIsNoEventTestUser;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionUpdated;
use App\StorableEvents\UserMembershipCreated;
use App\StorableEvents\UserMembershipDeleted;
use App\StorableEvents\UserMembershipImported;
use App\StorableEvents\UserMembershipUpdated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MembershipAggregate extends AggregateRoot
{
    use CustomerBasedAggregate;
    use ActiveDirectory;
    use Cards;
    use Github;
    use Subscription;

    public $currentlyAMember = false;

    public function __construct()
    {
        $class = static::class;

        $booted = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot'.class_basename($trait);

            if (method_exists($class, $method) && ! in_array($method, $booted)) {
                $this->$method();

                $booted[] = $method;
            }
        }
    }

    public function customerIsNoEventTestUser()
    {
        $this->recordThat(new CustomerIsNoEventTestUser($this->customerId));

        return $this;
    }

    public function createCustomer($customer)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerCreated($customer));

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function updateCustomer($customer)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerUpdated($customer));

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function deleteCustomer($customer)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerDeleted($customer['id']));

        return $this;
    }

    public function importCustomer($customer)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerImported($customer));

        $this->handleCards($customer);

        return $this;
    }

    public function createSubscription($subscription)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionCreated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function updateSubscription($subscription)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionUpdated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function importSubscription($subscription)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionImported($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function createUserMembership($membership)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipCreated($membership));

        return $this;
    }

    public function updateUserMembership($membership)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipUpdated($membership));

        return $this;
    }

    public function deleteUserMembership($membership)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipDeleted($membership));

        return $this;
    }

    public function importUserMembership($membership)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipImported($membership));

        return $this;
    }

    public function handleMembershipActivated()
    {
        $this->activateCardsNeedingActivation();
        $this->enableActiveDirectoryAccount();
    }

    public function handleMembershipDeactivated()
    {
        $this->deactivateAllCards();
        $this->disableActiveDirectoryAccount();
    }

    protected function applyMembershipActivated()
    {
        $this->currentlyAMember = true;
    }

    protected function applyMembershipDeactivated()
    {
        $this->currentlyAMember = false;
    }

    protected function applyCustomerIsNoEventTestUser()
    {
        $this->respondToEvents = false;
    }

    private function isActiveMember()
    {
        return $this->currentlyAMember;
    }
}
