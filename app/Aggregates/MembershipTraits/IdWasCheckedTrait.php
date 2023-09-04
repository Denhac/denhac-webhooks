<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\IdWasChecked;

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
