<?php

namespace App\External\WooCommerce\Api\members;

use App\External\ApiProgress;
use App\External\WooCommerce\Api\ApiCallFailed;
use App\External\WooCommerce\Api\WooCommerceApiMixin;
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
     * @throws ApiCallFailed
     */
    public function list(?ApiProgress $progress = null): Collection
    {
        return $this->getWithPaging('/wp-json/wc/v3/memberships/members', [
            RequestOptions::QUERY => [
                'role' => 'all',
            ],
        ], $progress);
    }

    /**
     * @throws ApiCallFailed
     *
     * @returns true if adding the membership succeeded
     */
    public function addMembership($woo_id, $plan_id, $meta_data = []): bool
    {
        $response = $this->client->post('/wp-json/wc/v3/memberships/members', [
            RequestOptions::JSON => [
                'customer_id' => $woo_id,
                'plan_id' => $plan_id,
                'meta_data' => $meta_data,
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $data = json_decode($response->getBody(), true);

        if ($response->getStatusCode() == Response::HTTP_BAD_REQUEST) {
            $code = $data['code'];
            if ($code == 'woocommerce_rest_wc_user_membership_exists') {
                return true; // Everything worked out fine.
            }
        }

        $this->jsonOrError($response);

        return false;
    }

    /**
     * @throws ApiCallFailed
     */
    public function get($woo_id): Collection
    {
        $response = $this->client->get("/wp-json/wc/v3/memberships/members/$woo_id");

        return $this->jsonOrError($response);
    }
}
