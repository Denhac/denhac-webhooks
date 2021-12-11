<?php

namespace App\Aggregates;

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
    use Cards;
    use Github;
    use Subscription;

    public bool $currentlyAMember = false;

    public function __construct()
    {
        $class = MembershipAggregate::class;

        $booted = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($class, $method) && !in_array($method, $booted)) {
                $this->$method();

                $booted[] = $method;
            }
        }
    }

    public function customerIsNoEventTestUser(): MembershipAggregate
    {
        $this->recordThat(new CustomerIsNoEventTestUser($this->customerId));

        return $this;
    }

    public function checkDenhacTestUser($customer): MembershipAggregate
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $metadata = collect($customer['meta_data']);
        $isDenhacTestUser = $metadata->firstWhere('key', 'is_denhac_test_user');

        if ($isDenhacTestUser == null) {
            return $this;
        }

        $value = $isDenhacTestUser['value'];

        if ($value === "1") {
            $this->customerIsNoEventTestUser();
        }

        return $this;
    }

    public function createCustomer($customer): MembershipAggregate
    {
        $this->checkDenhacTestUser($customer);

        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerCreated($customer));

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function updateCustomer($customer): MembershipAggregate
    {
        $this->checkDenhacTestUser($customer);

        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerUpdated($customer));

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function deleteCustomer($customer)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerDeleted($customer['id']));

        return $this;
    }

    public function importCustomer($customer)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerImported($customer));

        $this->handleCards($customer);

        return $this;
    }

    public function createSubscription($subscription)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionCreated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function updateSubscription($subscription)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionUpdated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function importSubscription($subscription)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionImported($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function createUserMembership($membership)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipCreated($membership));

        return $this;
    }

    public function updateUserMembership($membership)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipUpdated($membership));

        return $this;
    }

    public function deleteUserMembership($membership)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipDeleted($membership));

        return $this;
    }

    public function importUserMembership($membership)
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipImported($membership));

        return $this;
    }

    public function handleMembershipActivated()
    {
        $this->activateCardsNeedingActivation();
    }

    public function handleMembershipDeactivated()
    {
        $this->deactivateAllCards();
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
