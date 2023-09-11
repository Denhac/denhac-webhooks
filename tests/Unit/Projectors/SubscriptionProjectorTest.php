<?php

namespace Tests\Unit\Projectors;

use App\Models\Subscription;
use App\Projectors\SubscriptionProjector;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionDeleted;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubscriptionProjectorTest extends TestCase
{
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(SubscriptionProjector::class);
    }

    /** @test */
    public function importing_a_subscription_creates_subscription_in_db()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionImported($builder->toArray()));

        /** @var Subscription $subscription */
        $subscription = Subscription::find($builder->id);

        $this->assertNotNull($subscription);
        $this->assertEquals($builder->id, $subscription->id);
        $this->assertEquals($builder->id, $subscription->woo_id);
        $this->assertEquals($builder->status, $subscription->status);
        $this->assertEquals($builder->customer_id, $subscription->customer_id);
    }

    /** @test */
    public function creating_a_subscription_creates_subscription_in_db()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionCreated($builder->toArray()));

        /** @var Subscription $subscription */
        $subscription = Subscription::find($builder->id);

        $this->assertNotNull($subscription);
        $this->assertEquals($builder->id, $subscription->id);
        $this->assertEquals($builder->id, $subscription->woo_id);
        $this->assertEquals($builder->status, $subscription->status);
        $this->assertEquals($builder->customer_id, $subscription->customer_id);
    }

    /** @test */
    public function updating_a_non_existent_subscription_creates_subscription_in_db()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionUpdated($builder->toArray()));

        /** @var Subscription $subscription */
        $subscription = Subscription::find($builder->id);

        $this->assertNotNull($subscription);
        $this->assertEquals($builder->id, $subscription->id);
        $this->assertEquals($builder->id, $subscription->woo_id);
        $this->assertEquals($builder->status, $subscription->status);
        $this->assertEquals($builder->customer_id, $subscription->customer_id);
    }

    /** @test */
    public function updating_a_subscription_updates_subscription_in_db()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionCreated($builder->toArray()));

        /** @var Subscription $subscription */
        $subscription = Subscription::find($builder->id);

        $this->assertNotNull($subscription);
        $this->assertEquals($builder->id, $subscription->id);
        $this->assertEquals($builder->id, $subscription->woo_id);
        $this->assertEquals($builder->status, $subscription->status);
        $this->assertEquals($builder->customer_id, $subscription->customer_id);

        event(new SubscriptionUpdated($builder->toArray()));

        /** @var Subscription $subscription */
        $subscription = Subscription::find($builder->id);

        $this->assertNotNull($subscription);
        $this->assertEquals($builder->id, $subscription->id);
        $this->assertEquals($builder->id, $subscription->woo_id);
        $this->assertEquals($builder->status, $subscription->status);
        $this->assertEquals($builder->customer_id, $subscription->customer_id);
    }

    /** @test */
    public function deleting_a_subscription_deletes_in_db()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionCreated($builder->toArray()));

        $this->assertNotNull(Subscription::find($builder->id));

        event(new SubscriptionDeleted($builder->id));

        $this->assertNull(Subscription::find($builder->id));
    }

    /** @test */
    public function deleting_a_non_existent_subscription_throws_exception()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        $this->expectException(\Exception::class);

        event(new SubscriptionDeleted($builder->id));
    }

    /** @test */
    public function deleting_a_customer_deletes_associated_subscription()
    {
        $builder = $this->subscription();

        $this->assertNull(Subscription::find($builder->id));

        event(new SubscriptionCreated($builder->toArray()));

        $this->assertNotNull(Subscription::find($builder->id));

        event(new CustomerDeleted($builder->customer_id));

        $this->assertNull(Subscription::find($builder->id));
    }
}
