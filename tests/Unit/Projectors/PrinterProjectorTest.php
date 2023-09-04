<?php

namespace Tests\Unit\Projectors;

use App\Models\Printer3D;
use App\Projectors\PrinterProjector;
use App\StorableEvents\OctoPrintStatusUpdated;
use Carbon\Carbon;
use Tests\TestCase;

class PrinterProjectorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(PrinterProjector::class);
    }

    /** @test */
    public function OctoPrint_status_update_creates_equipment_model_if_none_existed_before()
    {
        $payload = $this->octoPrintUpdate();

        event(new OctoPrintStatusUpdated($payload->toArray()));

        /** @var Printer3D $equipment */
        $equipment = Printer3D::where('name', $payload->deviceIdentifier)
            ->first();

        $this->assertEquals($payload->deviceIdentifier, $equipment->name);
        $this->assertEquals(Printer3D::STATUS_PRINT_STARTED, $equipment->status);
        $expectedTime = Carbon::createFromTimestamp($payload->currentTime);
        $this->assertEquals($expectedTime, $equipment->status_updated_at);
    }

    /** @test */
    public function OctoPrint_status_update_updates_existing_model()
    {
        event(new OctoPrintStatusUpdated($this->octoPrintUpdate()->toArray()));

        $payload = $this->octoPrintUpdate()
            ->topic('Print Failed')
            ->currentTime(1601670000);

        $this->assertEquals(1, Printer3D::where('name', $payload->deviceIdentifier)->count());

        event(new OctoPrintStatusUpdated($payload->toArray()));

        /** @var Printer3D $equipment */
        $equipment = Printer3D::where('name', $payload->deviceIdentifier)
            ->first();

        $this->assertEquals($payload->deviceIdentifier, $equipment->name);
        $this->assertEquals(Printer3D::STATUS_PRINT_FAILED, $equipment->status);
        $expectedTime = Carbon::createFromTimestamp($payload->currentTime);
        $this->assertEquals($expectedTime, $equipment->status_updated_at);
    }
}
