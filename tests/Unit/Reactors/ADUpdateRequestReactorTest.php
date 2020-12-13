<?php

namespace Tests\Unit\Reactors;


use App\ADUpdateRequest;
use App\Reactors\ADUpdateRequestReactor;
use App\StorableEvents\ADUserDisabled;
use App\StorableEvents\ADUserEnabled;
use App\StorableEvents\ADUserToBeDisabled;
use App\StorableEvents\ADUserToBeEnabled;
use Tests\TestCase;

class ADUpdateRequestReactorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandler(ADUpdateRequestReactor::class);
    }

    /** @test */
    public function ad_user_to_be_enabled_creates_update_request()
    {
        $event = new ADUserToBeEnabled(1);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNotNull($model);
        $this->assertEquals(ADUpdateRequest::ACTIVATION_TYPE, $model->type);
    }

    /** @test */
    public function ad_user_to_be_disabled_creates_update_request()
    {
        $event = new ADUserToBeDisabled(1);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNotNull($model);
        $this->assertEquals(ADUpdateRequest::DEACTIVATION_TYPE, $model->type);
    }

    /** @test */
    public function ad_user_enabled_deletes_activation_update_request()
    {
        $customerId = 1;
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::ACTIVATION_TYPE,
            'customer_id' => $customerId,
        ]);

        $event = new ADUserEnabled($customerId);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNull($model);
    }

    /** @test */
    public function ad_user_disabled_deletes_deactivation_update_request()
    {
        $customerId = 1;
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::DEACTIVATION_TYPE,
            'customer_id' => $customerId,
        ]);

        $event = new ADUserDisabled($customerId);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNull($model);
    }

    /** @test */
    public function ad_user_enabled_does_not_delete_deactivation_update_request()
    {
        $customerId = 1;
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::DEACTIVATION_TYPE,
            'customer_id' => $customerId,
        ]);

        $event = new ADUserEnabled($customerId);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNotNull($model);
    }

    /** @test */
    public function ad_user_disabled_does_not_delete_activation_update_request()
    {
        $customerId = 1;
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::ACTIVATION_TYPE,
            'customer_id' => $customerId,
        ]);

        $event = new ADUserDisabled($customerId);

        event($event);

        /** @var ADUpdateRequest $model */
        $model = ADUpdateRequest::where('customer_id', $event->customerId)->first();

        $this->assertNotNull($model);
    }
}
