<?php

namespace Tests\Unit\Aggregates;

use App\Aggregates\OctoPrintAggregate;
use App\StorableEvents\OctoPrintStatusUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class OctoPrintAggregateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function octoprint_updated_event_is_sent_when_handling_webhook_call()
    {
        $payload = $this->octoPrintUpdate()->toArray();

        OctoPrintAggregate::fake()
            ->handle($payload)
            ->assertRecorded([
                new OctoPrintStatusUpdated($payload),
            ]);
    }
}
