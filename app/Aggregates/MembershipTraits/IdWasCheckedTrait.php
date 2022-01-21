<?php

namespace App\Aggregates\MembershipTraits;

use App\FeatureFlags;
use App\StorableEvents\IdWasChecked;
use App\StorableEvents\MembershipActivated;
use YlsIdeas\FeatureFlags\Facades\Features;

trait IdWasCheckedTrait
{
    public bool $idWasChecked = false;

    private function handleIdCheck($customer)
    {
        $metadata = collect($customer['meta_data']);
        $idWasCheckedValue = $metadata
                ->where('key', 'id_was_checked')
                ->first()['value'] ?? null;

        if (!is_null($idWasCheckedValue) && !$this->idWasChecked) {
            $this->recordThat(new IdWasChecked($this->customerId));

            if($this->activeFullMemberPlan) {
                if (Features::accessible(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE)) {
                    $this->activateMembership();
                }
            }
        }
    }

    public function applyIdWasChecked(IdWasChecked $_)
    {
        $this->idWasChecked = true;
    }
}
