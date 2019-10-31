<?php

namespace App\WooCommerce\Api\webhook;


use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class WebhookApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ApiCallFailed
     */
    public function list()
    {
        return $this->getWithPaging("/wp-json/wc/v3/webhooks");
    }

    /**
     * @param $topicKey
     * @param $topicName
     * @param $deliveryUrl
     * @param null $secret
     * @return Collection
     * @throws ApiCallFailed
     */
    public function create($topicKey, $topicName, $deliveryUrl, $secret = null)
    {
        $json = [
            "topic" => $topicKey,
            "name" => $topicName,
            "delivery_url" => $deliveryUrl
        ];

        if($secret != null) {
            $json["secret"] = $secret;
        }

        $response = $this->client
            ->post("/wp-json/wc/v3/webhooks", [
                RequestOptions::JSON => $json
            ]);

        return $this->jsonOrError($response);
    }
}
