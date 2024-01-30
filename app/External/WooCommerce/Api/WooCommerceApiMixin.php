<?php

namespace App\External\WooCommerce\Api;

use App\External\ApiProgress;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
     * @throws ApiCallFailed
     */
    private function jsonOrError(ResponseInterface $response): Collection
    {
        $this->handleError($response);

        return collect(json_decode($response->getBody(), true));
    }

    /**
     * @throws ApiCallFailed
     */
    private function getWithPaging($url, array $options = [], ApiProgress $progress = null): Collection
    {
        if (! Arr::has($options, RequestOptions::QUERY)) {
            $options[RequestOptions::QUERY] = [];
        }

        // If you don't order by id, the default is post_date which causes issues when two posts have the exact same timestamp.
        // Forcing the order here isn't my favorite thing, but it can always be sorted later
        $options[RequestOptions::QUERY]['orderby'] = 'id';

        if (! Arr::has($options[RequestOptions::QUERY], 'per_page')) {
            $options[RequestOptions::QUERY]['per_page'] = 100;
        }

        try {
            $initialResponse = $this->client->get($url, $options);
        } catch (GuzzleException $ex) {
            throw new ApiCallFailed('API call failed', 0, $ex);
        }

        $this->handleError($initialResponse);

        $responseData = $this->jsonOrError($initialResponse);

        $totalPages = (int) $initialResponse->getHeader('X-WP-TotalPages')[0];

        if (! is_null($progress)) {
            $progress->setProgress(1, $totalPages);
        }

        for ($currentPage = 2; $currentPage <= $totalPages; $currentPage++) {
            $options[RequestOptions::QUERY]['page'] = $currentPage;

            try {
                $response = $this->client->get($url, $options);
            } catch (GuzzleException $ex) {
                throw new ApiCallFailed('API call failed', 0, $ex);
            }

            $responseData = $responseData->merge($this->jsonOrError($response));

            if (! is_null($progress)) {
                $progress->setProgress($currentPage, $totalPages);
            }
        }

        return $responseData;
    }

    /**
     * @throws ApiCallFailed
     */
    private function handleError(ResponseInterface $response): void
    {
        if ($response->getStatusCode() != Response::HTTP_CREATED &&
            $response->getStatusCode() != Response::HTTP_OK) {
            $errorMessage = "Unable to process response (Status: {$response->getStatusCode()})";
            throw new ApiCallFailed($errorMessage);
        }
    }
}
