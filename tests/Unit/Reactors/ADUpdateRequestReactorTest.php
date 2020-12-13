<?php

namespace Tests\Unit\Reactors;


use App\ADUpdateRequest;
use App\Reactors\ADUpdateRequestReactor;
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
}
