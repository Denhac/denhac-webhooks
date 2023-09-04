<?php

namespace Tests\Unit\Reactors;

use App\Models\CardUpdateRequest;
use App\Reactors\CardUpdateRequestReactor;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Tests\TestCase;

class CardUpdateRequestReactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(CardUpdateRequestReactor::class);
    }

    /** @test */
    public function card_sent_for_activation_creates_update_request()
    {
        $event = new CardSentForActivation(1, 1234);

        $this->assertEmpty(CardUpdateRequest::all());

        event($event);

        /** @var CardUpdateRequest $cardUpdateRequest */
        $cardUpdateRequest = CardUpdateRequest::first();

        $this->assertEquals(CardUpdateRequest::ACTIVATION_TYPE, $cardUpdateRequest->type);
        $this->assertEquals(1, $cardUpdateRequest->customer_id);
        $this->assertEquals(1234, $cardUpdateRequest->card);
    }

    /** @test */
    public function card_sent_for_deactivation_creates_update_request()
    {
        $event = new CardSentForDeactivation(1, 1234);

        $this->assertEmpty(CardUpdateRequest::all());

        event($event);

        /** @var CardUpdateRequest $cardUpdateRequest */
        $cardUpdateRequest = CardUpdateRequest::first();

        $this->assertEquals(CardUpdateRequest::DEACTIVATION_TYPE, $cardUpdateRequest->type);
        $this->assertEquals(1, $cardUpdateRequest->customer_id);
        $this->assertEquals(1234, $cardUpdateRequest->card);
    }
}
