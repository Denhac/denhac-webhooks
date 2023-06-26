<?php

namespace App\Aggregates\MembershipTraits;


use App\StorableEvents\WaiverAssignedToCustomer;
use App\Waiver;
use Illuminate\Support\Collection;

trait WaiverTrait
{
    public Collection $waivers;
    public bool $membershipWaiverSigned = false;

    public function bootWaiverTrait(): void
    {
        $this->waivers = collect();
    }

    protected function applyWaiverAssignedToCustomer(WaiverAssignedToCustomer $event)
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('waiver_id', $event->waiverId)->first();

        $membershipWaiverTemplateId = config('denhac.waiver.membership_waiver_template_id');

        if($membershipWaiverTemplateId == $waiver->template_id) {
            $this->membershipWaiverSigned = true;
        }

        $this->waivers->add($waiver);
    }
}
