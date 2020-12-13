<?php

namespace Tests\Feature\App\Http\Controllers;

use App\ADUpdateRequest;
use App\Customer;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ADUpdateRequestsControllerTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function can_retrieve_list_of_ad_updates()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'username' => $this->faker->userName,
            'email' => $this->faker->email,
            'woo_id' => 1,
            'member' => true,
        ]);

        /** @var ADUpdateRequest $enableRequest */
        $enableRequest = ADUpdateRequest::create([
            'type' => ADUpdateRequest::ACTIVATION_TYPE,
            'customer_id' => $customer->id,
        ]);

        /** @var ADUpdateRequest $disableRequest */
        $disableRequest = ADUpdateRequest::create([
            'type' => ADUpdateRequest::DEACTIVATION_TYPE,
            'customer_id' => $customer->id,
        ]);

        $response = $this
            ->be($this->apiUser, 'api')
            ->get('/api/ad_updates');
        $response->assertJson([
            'data' => [
                [
                    'id' => $enableRequest->id,
                    'method' => ADUpdateRequest::ACTIVATION_TYPE,
                    'username' => $customer->username,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name
                ],
                [
                    'id' => $disableRequest->id,
                    'method' => ADUpdateRequest::DEACTIVATION_TYPE,
                    'username' => $customer->username,
                ],
            ]
        ]);
    }
}
