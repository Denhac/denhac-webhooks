<?php

namespace App\Http\Resources;

use App\CardUpdateRequest;
use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int id
 * @property string card
 * @property string type
 * @property Customer customer
 */
class CardUpdateRequestResource extends JsonResource
{
    private const COMPANY_DENHAC = 'DenHac'; // This is how it exists in the card access system

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'method' => $this->getUpdateMethod(),
            'id' => $this->id,
            'card' => $this->card,
            'first_name' => $this->when($this->type == CardUpdateRequest::ACTIVATION_TYPE, $this->customer->first_name),
            'last_name' => $this->when($this->type == CardUpdateRequest::ACTIVATION_TYPE, $this->customer->last_name),
            'company' => self::COMPANY_DENHAC,
        ];
    }

    private function getUpdateMethod()
    {
        if ($this->type == CardUpdateRequest::ACTIVATION_TYPE) {
            return 'enable';
        }

        if ($this->type == CardUpdateRequest::DEACTIVATION_TYPE) {
            return 'disable';
        }
    }
}
