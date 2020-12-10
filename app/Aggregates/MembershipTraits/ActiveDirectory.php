<?php

namespace App\Aggregates\MembershipTraits;


use App\StorableEvents\ADUserToBeDisabled;
use App\StorableEvents\ADUserToBeEnabled;

trait ActiveDirectory
{
    public function enableActiveDirectoryAccount()
    {
        $this->recordThat(new ADUserToBeEnabled($this->customerId));
    }

    public function disableActiveDirectoryAccount()
    {
        $this->recordThat(new ADUserToBeDisabled($this->customerId));
    }
}
