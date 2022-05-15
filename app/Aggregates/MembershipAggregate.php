<?php

namespace App\Aggregates;

use App\Aggregates\MembershipTraits\Cards;
use App\Aggregates\MembershipTraits\Github;
use App\Aggregates\MembershipTraits\IdWasCheckedTrait;
use App\Aggregates\MembershipTraits\Subscription;
use App\Aggregates\MembershipTraits\UserMembership;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerIsNoEventTestUser;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
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
    use UserMembership;
    use IdWasCheckedTrait;

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

    public function customerIsNoEventTestUser(): static
    {
        $this->recordThat(new CustomerIsNoEventTestUser($this->customerId));

        return $this;
    }

    public function checkDenhacTestUser($customer): static
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

    public function createCustomer($customer): static
    {
        $this->checkDenhacTestUser($customer);

        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerCreated($customer));

        $this->handleIdCheck($customer);

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function updateCustomer($customer): static
    {
        $this->checkDenhacTestUser($customer);

        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerUpdated($customer));

        $this->handleIdCheck($customer);

        $this->handleCards($customer);

        $this->handleGithub($customer);

        return $this;
    }

    public function deleteCustomer($customer): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerDeleted($customer['id']));

        return $this;
    }

    public function importCustomer($customer): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerImported($customer));

        $this->handleIdCheck($customer);

        $this->handleCards($customer);

        return $this;
    }

    public function createSubscription($subscription): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionCreated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function updateSubscription($subscription): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionUpdated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function importSubscription($subscription): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionImported($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function createUserMembership($membership): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipCreated($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function updateUserMembership($membership): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipUpdated($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function deleteUserMembership($membership): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipDeleted($membership));

        return $this;
    }

    public function importUserMembership($membership): static
    {
        if (!$this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipImported($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function activateMembershipIfNeeded()
    {
        if($this->currentlyAMember) {
            return;
        }

        $this->recordThat(new MembershipActivated($this->customerId));

        $this->activateCardsNeedingActivation();
    }

    public function deactivateMembershipIfNeeded()
    {
        if(! $this->currentlyAMember) {
            return;
        }

        $this->recordThat(new MembershipDeactivated($this->customerId));

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

    private function isActiveMember(): bool
    {
        return $this->currentlyAMember;
    }
}
