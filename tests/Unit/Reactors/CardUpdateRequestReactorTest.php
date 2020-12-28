<?php

namespace Tests\Unit\Reactors;

use App\Jobs\IssueCardUpdateRequest;
use App\Reactors\CardUpdateRequestReactor;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CardUpdateRequestReactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(CardUpdateRequestReactor::class);

        Bus::fake(IssueCardUpdateRequest::class);
    }

    /** @test */
    public function card_sent_for_activation_dispatches_update_request()
    {
        $event = new CardSentForActivation(1, 1234);

        event($event);

        Bus::assertDispatched(IssueCardUpdateRequest::class,
            function (IssueCardUpdateRequest $job) use ($event) {
                return $job->cardSentForRequest->wooCustomerId == $event->wooCustomerId &&
                $job->cardSentForRequest->cardNumber == $event->cardNumber &&
                get_class($job->cardSentForRequest) == CardSentForActivation::class;
            });
    }

    /** @test */
    public function card_sent_for_deactivation_dispatches_update_request()
    {
        $event = new CardSentForDeactivation(1, 1234);

        event($event);

        Bus::assertDispatched(IssueCardUpdateRequest::class,
            function (IssueCardUpdateRequest $job) use ($event) {
                return $job->cardSentForRequest->wooCustomerId == $event->wooCustomerId &&
                    $job->cardSentForRequest->cardNumber == $event->cardNumber &&
                    get_class($job->cardSentForRequest) == CardSentForDeactivation::class;
            });
    }
}
