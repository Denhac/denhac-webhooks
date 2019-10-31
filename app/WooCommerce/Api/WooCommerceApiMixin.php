<?php

namespace App\WooCommerce\Api;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

trait WooCommerceApiMixin
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param ResponseInterface $response
     * @return Collection
     * @throws ApiCallFailed
     */
    private function jsonOrError($response)
    {
        $this->handleError($response);

        return collect(json_decode($response->getBody(), true));
    }

    /**
     * @param $url
     * @param array $options
     * @return Collection
     * @throws ApiCallFailed
     */
    private function getWithPaging($url, $options = [])
    {
        $initialResponse = $this->client->get($url, $options);

        $this->handleError($initialResponse);

        $responseData = $this->jsonOrError($initialResponse);

        $totalPages = (int) $initialResponse->getHeader("X-WP-TotalPages")[0];

        if(!Arr::has($options, RequestOptions::QUERY)) {
            $options[RequestOptions::QUERY] = [];
        }

        for ($i = 2; $i <= $totalPages; $i++) {
            $options[RequestOptions::QUERY]["page"] = $i;
            $response = $this->client->get($url, $options);
            $responseData = $responseData->merge($this->jsonOrError($response));
        }

        return $responseData;
    }

    /**
     * @param ResponseInterface $response
     * @throws ApiCallFailed
     */
    private function handleError($response): void
    {
        if ($response->getStatusCode() != Response::HTTP_CREATED &&
            $response->getStatusCode() != Response::HTTP_OK) {
            $errorMessage = "Unable to process response (Status: {$response->getStatusCode()})";
            throw new ApiCallFailed($errorMessage);
        }
    }
}
