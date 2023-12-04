<?php

namespace App\Aggregates;

use App\Aggregates\MembershipTraits\Cards;
use App\Aggregates\MembershipTraits\Github;
use App\Aggregates\MembershipTraits\IdWasCheckedTrait;
use App\Aggregates\MembershipTraits\Subscription;
use App\Aggregates\MembershipTraits\UserMembership;
use App\Aggregates\MembershipTraits\WaiverTrait;
use App\Models\Waiver;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\Waiver\ManualBootstrapWaiverNeeded;
use App\StorableEvents\Waiver\WaiverAssignedToCustomer;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerIsNoEventTestUser;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionDeleted;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipDeleted;
use App\StorableEvents\WooCommerce\UserMembershipImported;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MembershipAggregate extends AggregateRoot
{
    use CustomerBasedAggregate;
    use Cards;
    use Github;
    use Subscription;
    use UserMembership;
    use IdWasCheckedTrait;
    use WaiverTrait;

    public bool $currentlyAMember = false;

    public function __construct()
    {
        $class = MembershipAggregate::class;

        $booted = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot'.class_basename($trait);

            if (method_exists($class, $method) && ! in_array($method, $booted)) {
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
        if (! $this->respondToEvents) {
            return $this;
        }

        $metadata = collect($customer['meta_data']);
        $isDenhacTestUser = $metadata->firstWhere('key', 'is_denhac_test_user');

        if ($isDenhacTestUser == null) {
            return $this;
        }

        $value = $isDenhacTestUser['value'];

        if ($value === '1') {
            $this->customerIsNoEventTestUser();
        }

        return $this;
    }

    public function createCustomer($customer): static
    {
        $this->checkDenhacTestUser($customer);

        if (! $this->respondToEvents) {
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

        if (! $this->respondToEvents) {
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
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerDeleted($customer['id']));

        return $this;
    }

    public function importCustomer($customer): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CustomerImported($customer));

        $this->handleIdCheck($customer);

        $this->handleCards($customer);

        return $this;
    }

    public function createSubscription($subscription): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionCreated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function updateSubscription($subscription): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionUpdated($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function importSubscription($subscription): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionImported($subscription));

        $this->handleSubscriptionStatus($subscription['id'], $subscription['status']);

        return $this;
    }

    public function deleteSubscription($subscription): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new SubscriptionDeleted($subscription['id']));

        return $this;
    }

    public function createUserMembership($membership): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipCreated($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function updateUserMembership($membership): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipUpdated($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function deleteUserMembership($membership): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipDeleted($membership));

        return $this;
    }

    public function importUserMembership($membership): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new UserMembershipImported($membership));

        $this->handleUserMembership($membership);

        return $this;
    }

    public function assignWaiver(Waiver $waiver): static
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new WaiverAssignedToCustomer($waiver->waiver_id, $this->customerId));

        $this->activateCardsNeedingActivation();

        return $this;
    }

    public function bootstrapWaiverRequirement(): static
    {
        if ($this->manualBootstrapTriggered) {
            return $this;
        } elseif ($this->membershipWaiverSigned) {
            return $this;
        }

        $this->recordThat(new ManualBootstrapWaiverNeeded($this->customerId));

        $this->deactivateAllCards();

        return $this;
    }

    public function activateMembershipIfNeeded(): void
    {
        if ($this->currentlyAMember) {
            return;
        }

        $this->recordThat(new MembershipActivated($this->customerId));

        $this->activateCardsNeedingActivation();
    }

    public function deactivateMembershipIfNeeded(): void
    {
        if (! $this->currentlyAMember) {
            return;
        }

        $this->recordThat(new MembershipDeactivated($this->customerId));

        $this->deactivateAllCards();
    }

    protected function applyMembershipActivated()
    {
        $this->currentlyAMember = true;

        // We're going to force enable any cards here by saying they all need activation.
        // This is mostly to handle the case of someone re-signing up rather than for new users.
        $this->cardsNeedingActivation = collect($this->cardsOnAccount);
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

    private function shouldHavePhysicalBuildingAccess(): bool
    {
        return $this->isActiveMember() && $this->membershipWaiverSigned;
    }
}
