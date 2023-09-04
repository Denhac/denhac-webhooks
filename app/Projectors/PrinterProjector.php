<?php

namespace App\Projectors;

use App\Customer;
use App\Printer3D;
use App\StorableEvents\OctoPrintStatusUpdated;
use Carbon\Carbon;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class PrinterProjector extends Projector
{
    public function onStartingEventReplay()
    {
        Customer::truncate();
    }

    public function onOctoPrintStatusUpdated(OctoPrintStatusUpdated $event)
    {
        $deviceIdentifier = $event->payload['deviceIdentifier'];

        /** @var Printer3D $printer */
        $printer = Printer3D::whereName($deviceIdentifier)->first();

        if (is_null($printer)) {
            Printer3D::create([
                'name' => $deviceIdentifier,
                'status' => Printer3D::getStatus($event->payload['topic']),
                'status_updated_at' => Carbon::createFromTimestamp($event->payload['currentTime']),
            ]);
        } else {
            $printer->status = Printer3D::getStatus($event->payload['topic']);
            $printer->status_updated_at = Carbon::createFromTimestamp($event->payload['currentTime']);
            $printer->save();
        }
    }
}
