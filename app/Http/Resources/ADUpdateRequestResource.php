<?php

namespace App\Http\Resources;

use App\ADUpdateRequest;
use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int id
 * @property string type
 * @property Customer customer
 */
class ADUpdateRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'method' => $this->type,
            'username' => $this->customer->username,
            $this->mergeWhen($this->type == ADUpdateRequest::ACTIVATION_TYPE, [
                'first_name' => $this->customer->first_name,
                'last_name' => $this->customer->last_name,
            ]),
        ];
    }
}
