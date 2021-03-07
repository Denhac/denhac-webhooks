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
        $payload = [
            "deviceIdentifier" => "Test Printer",
            "job" => [
                "averagePrintTime" => null,
                "lastPrintTime" => null,
                "user" => "test_user",
                "file" => [
                    "origin" => "local",
                    "name" => "something.gcode",
                    "date" => 1601669166,
                    "path" => "folder/something.gcode",
                    "display" => "something.gcode",
                    "size" => 300000,
                ],
                "estimatedPrintTime" => null,
                "filament" => null,
            ],
            "offsets" => [],
            "apiSecret" => "foo",
            "topic" => "Print Started",
            "state" => [
                "text" => "Printing",
            ],
            "meta" => null,
            "currentTime" => 1601669184,
            "currentZ" => null,
            "progress" => [
                "completion" => 0.00093758100743311,
                "printTimeLeftOrigin" => "linear",
                "printTime" => 0,
                "printTimeLeft" => null,
                "filepos" => 324,
            ],
            "message" => "Your print has started.",
        ];

        OctoPrintAggregate::fake()
            ->handle($payload)
            ->assertRecorded([
                new OctoPrintStatusUpdated($payload),
            ]);
    }
}
