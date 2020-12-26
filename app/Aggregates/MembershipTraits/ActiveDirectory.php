<?php

namespace App\Aggregates\MembershipTraits;


use App\ADUpdateRequest;
use App\StorableEvents\ADUserDisabled;
use App\StorableEvents\ADUserEnabled;
use App\StorableEvents\ADUserToBeDisabled;
use App\StorableEvents\ADUserToBeEnabled;
use Exception;

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

    public function updateADStatus(ADUpdateRequest $updateRequest, $status)
    {
        if(! $this->respondToEvents) {
            return $this;
        }

        if($status == ADUpdateRequest::STATUS_SUCCESS) {
            if($updateRequest->type == ADUpdateRequest::ACTIVATION_TYPE) {
                $this->recordThat(new ADUserEnabled($this->customerId));
            } else if ($updateRequest->type == ADUpdateRequest::DEACTIVATION_TYPE) {
                $this->recordThat(new ADUserDisabled($this->customerId));
            } else {
                $message = "Active Directory update request type wasn't one of the expected values: "
                . $updateRequest->type;
                throw new Exception($message);
            }
        } else {
            $message = "Active Directory update (Customer: $updateRequest->customer_id, "
                . "Type: $updateRequest->type) not successful";
            throw new Exception($message);
        }

        return $this;
    }
}
