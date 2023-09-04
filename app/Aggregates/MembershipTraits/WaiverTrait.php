<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\ManualBootstrapWaiverNeeded;
use App\StorableEvents\WaiverAssignedToCustomer;
use App\Models\Waiver;
use Illuminate\Support\Collection;

trait WaiverTrait
{
    public Collection $waivers;

    public bool $membershipWaiverSigned = false;

    public bool $manualBootstrapTriggered = false;

    public function bootWaiverTrait(): void
    {
        $this->waivers = collect();
    }

    protected function applyWaiverAssignedToCustomer(WaiverAssignedToCustomer $event): void
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('waiver_id', $event->waiverId)->first();

        $membershipWaiverTemplateId = config('denhac.waiver.membership_waiver_template_id');

        if ($membershipWaiverTemplateId == $waiver->template_id) {
            $this->membershipWaiverSigned = true;
        }

        $this->waivers->add($waiver);
    }

    protected function applyManualBootstrapWaiverNeeded(ManualBootstrapWaiverNeeded $event): void
    {
        $this->manualBootstrapTriggered = true;
    }
}
