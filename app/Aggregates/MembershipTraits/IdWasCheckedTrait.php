<?php

namespace App\Aggregates\MembershipTraits;

use App\FeatureFlags;
use App\StorableEvents\IdWasChecked;
use App\StorableEvents\MembershipActivated;
use YlsIdeas\FeatureFlags\Facades\Features;

trait IdWasCheckedTrait
{
    public bool $idWasChecked = false;

    private function handleIdCheck($customer): void
    {
        $metadata = collect($customer['meta_data']);
        $idWasCheckedValue = $metadata
            ->where('key', 'id_was_checked')
            ->first()['value'] ?? null;

        if (! is_null($idWasCheckedValue) && ! $this->idWasChecked) {
            $this->recordThat(new IdWasChecked($this->customerId));

            if ($this->activeFullMemberPlan) {
                $this->activateMembershipIfNeeded();
            }
        }
    }

    public function applyIdWasChecked(IdWasChecked $_): void
    {
        $this->idWasChecked = true;
    }
}
