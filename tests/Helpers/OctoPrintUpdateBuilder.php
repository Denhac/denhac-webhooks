<?php

namespace Tests\Helpers;

/**
 * Class OctoPrintUpdateBuilder
 *
 * @property string deviceIdentifier
 * @property string topic
 * @property int currentTime
 */
class OctoPrintUpdateBuilder extends BaseBuilder
{
    public function __construct()
    {
        $this->data = [
            'deviceIdentifier' => 'Test Printer',
            'job' => [
                'averagePrintTime' => null,
                'lastPrintTime' => null,
                'user' => 'test_user',
                'file' => [
                    'origin' => 'local',
                    'name' => 'something.gcode',
                    'date' => 1601669166,
                    'path' => 'folder/something.gcode',
                    'display' => 'something.gcode',
                    'size' => 300000,
                ],
                'estimatedPrintTime' => null,
                'filament' => null,
            ],
            'offsets' => [],
            'apiSecret' => 'foo',
            'topic' => 'Print Started',
            'state' => [
                'text' => 'Printing',
            ],
            'meta' => null,
            'currentTime' => 1601669184,
            'currentZ' => null,
            'progress' => [
                'completion' => 0.00093758100743311,
                'printTimeLeftOrigin' => 'linear',
                'printTime' => 0,
                'printTimeLeft' => null,
                'filepos' => 324,
            ],
            'message' => 'Your print has started.',
        ];
    }

    public function topic(string $topic): static
    {
        $this->data['topic'] = $topic;

        return $this;
    }

    public function currentTime(int $time): static
    {
        $this->data['currentTime'] = $time;

        return $this;
    }
}
