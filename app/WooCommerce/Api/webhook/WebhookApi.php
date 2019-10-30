<?php

namespace App\WooCommerce\Api\webhook;


use App\WooCommerce\Api\ApiCallFailed;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

class WebhookApi
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param ResponseInterface $response
     * @return Collection
     * @throws ApiCallFailed
     */
    private function jsonOrError($response)
    {
        if ($response->getStatusCode() != Response::HTTP_CREATED &&
            $response->getStatusCode() != Response::HTTP_OK) {
            $errorMessage = "Unable to process webhook response (Status: {$response->getStatusCode()})";
            throw new ApiCallFailed($errorMessage);
        }

        return collect(json_decode($response->getBody(), true));
    }

    /**
     * @throws ApiCallFailed
     */
    public function list()
    {
        $response = $this->client
            ->get("/wp-json/wc/v3/webhooks");

        return $this->jsonOrError($response);
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
