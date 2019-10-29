<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Console\Command;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Psy\Util\Json;

class SetUpDenhacWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:woocommerce-hooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Up WooCommerce Webhooks';

    protected $topics = [
        "customer.created" => "Customer Created",
        "customer.updated" => "Customer Updated",
        "customer.deleted" => "Customer Deleted",
        "order.created" => "Order Created",
        "order.updated" => "Order Updated",
        "order.deleted" => "Order Deleted",
        "subscription.created" => "Subscription Created",
        "subscription.updated" => "Subscription Updated",
        "subscription.deleted" => "Subscription Deleted",
        "subscription.switched" => "Subscription Switched",
    ];

    /**
     * @var Client
     */
    private $guzzleClient;
    /**
     * @var string
     */
    private $deliveryUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->guzzleClient = new Client([
            'base_uri' => config('denhac.url'),
            'auth' => [
                config('denhac.rest.key'),
                config('denhac.rest.secret'),
            ],
        ]);


        $this->deliveryUrl = (string)url('webhooks/denhac-org', [], true);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $existingWebhooks = $this->getExistingWebhooks();

        collect($this->topics)
            ->each(function ($topicName, $topicKey) use ($existingWebhooks) {
                $this->createOrActivateWebhook($existingWebhooks, $topicKey, $topicName);
            });
    }

    private function getExistingWebhooks()
    {
        $response = $this->guzzleClient
            ->get("/wp-json/wc/v3/webhooks");

        if ($response->getStatusCode() != Response::HTTP_OK) {
            $errorMessage = "Unable to read list of webhooks (Status: {$response->getStatusCode()})";
            throw new Exception($errorMessage);
        }

        return collect(json_decode($response->getBody(), true));
    }

    private function createWebhook($topicKey, $topicName)
    {
        $response = $this->guzzleClient
            ->post("/wp-json/wc/v3/webhooks", [
                RequestOptions::JSON => [
                    "name" => $topicName,
                    "topic" => $topicKey,
                    "delivery_url" => $this->deliveryUrl,
                    "secret" => env('DENHAC_ORG_SIGNING_SECRET'),
                ]
            ]);

        if ($response->getStatusCode() != Response::HTTP_CREATED) {
            $errorMessage = "Unable to create Webhook (Status: {$response->getStatusCode()})";
            throw new Exception($errorMessage);
        }

        return json_decode($response->getBody(), true);
    }

    private function createOrActivateWebhook(Collection $existingWebhooks, $topicKey, $topicName)
    {
        $filtered = $existingWebhooks
            ->filter(function($existingWebhook) use ($topicKey) {
                return $existingWebhook["topic"] == $topicKey &&
                    $existingWebhook["delivery_url"] = $this->deliveryUrl;
            });

        $count = $filtered->count();
        if($count == 0) {
            $this->line("Creating hook for topic {$topicKey}");
            try {
                $this->createWebhook($topicKey, $topicName);
            } catch (Exception $e) {
                $this->line("Creating hook failed!");
                $this->line($e->getMessage());
            }
        } else if($count == 1) {
            $this->line("Hook for topic {$topicKey} already exists");
            $hook = $filtered->first();

            // Active is fine, assume paused is for a good reason and disabled is an error

            if($hook["status"] == "active") {
                $this->line("Hook is active. Woot!");
            } else if($hook["status"] == "paused") {
                $this->line("Hook is paused. We won't do anything");
            } else if($hook["status"] == "disabled") {
                $this->line("Hook for topic {$topicKey} is disabled, trying to enable it");
                try {
                    $this->activateWebhook($hook['id']);
                } catch (Exception $e) {
                    $this->line("Enabling hook failed");
                    $this->line($e->getMessage());
                }
            }
        } else {
            $this->line("We found {$count} hooks for topic {$topicKey} which is more than we expected");
        }
    }

    private function activateWebhook($id)
    {
        $response = $this->guzzleClient
            ->put("/wp-json/wc/v3/webhooks/{$id}", [
                RequestOptions::JSON => [
                    "status" => "active",
                ]
            ]);


        if ($response->getStatusCode() != Response::HTTP_OK) {
            $errorMessage = "Unable to set Webhook to active status (Status: {$response->getStatusCode()})";
            throw new Exception($errorMessage);
        }
    }
}
