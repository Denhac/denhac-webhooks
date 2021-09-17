<?php

namespace App\WooCommerce\Api\members;

use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class MembersApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Collection
     * @throws ApiCallFailed
     */
    public function list()
    {
        return $this->getWithPaging('/wp-json/wc/v3/memberships/members', [
            RequestOptions::QUERY => [
                'role' => 'all',
            ],
        ]);
    }

    /**
     * @param $woo_id
     * @param $plan_id
     * @return Collection
     * @throws ApiCallFailed
     */
    public function addMembership($woo_id, $plan_id)
    {
        $response = $this->client->post('/wp-json/wc/v3/memberships/members', [
            RequestOptions::JSON => [
                'customer_id' => $woo_id,
                'plan_id' => $plan_id,
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $data = json_decode($response->getBody(), true);

        if($response->getStatusCode() == Response::HTTP_BAD_REQUEST) {
            $code = $data['code'];
            if($code == 'woocommerce_rest_wc_user_membership_exists') {
                return null; // Everything worked out fine.
            }
        }

        $this->jsonOrError($response);
    }

    /**
     * @param $woo_id
     * @return Collection
     * @throws ApiCallFailed
     */
    public function get($woo_id)
    {
        $response = $this->client->get('/wp-json/wc/v3/memberships/members', [
            RequestOptions::QUERY => [
                'customer' => $woo_id,
            ],
        ]);

        return $this->jsonOrError($response);
    }
}
