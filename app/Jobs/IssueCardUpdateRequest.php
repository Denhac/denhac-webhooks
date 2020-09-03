<?php

namespace App\Jobs;

use App\CardUpdateRequest;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IssueCardUpdateRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CardSentForActivation|CardSentForDeactivation
     */
    public $cardSentForRequest;

    /**
     * Create a new job instance.
     *
     * @param CardSentForActivation|CardSentForDeactivation $cardSentForRequest
     */
    public function __construct($cardSentForRequest)
    {
        $this->cardSentForRequest = $cardSentForRequest;
        $this->onQueue("backups");
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        if ($this->cardSentForRequest instanceof CardSentForActivation) {
            CardUpdateRequest::create([
                'type' => CardUpdateRequest::ACTIVATION_TYPE,
                'customer_id' => $this->cardSentForRequest->wooCustomerId,
                'card' => $this->cardSentForRequest->cardNumber,
            ]);
        } elseif ($this->cardSentForRequest instanceof CardSentForDeactivation) {
            CardUpdateRequest::create([
                'type' => CardUpdateRequest::DEACTIVATION_TYPE,
                'customer_id' => $this->cardSentForRequest->wooCustomerId,
                'card' => $this->cardSentForRequest->cardNumber,
            ]);
        } else {
            $cls = get_class($this->cardSentForRequest);
            throw new Exception("Unknown issuing request type: $cls");
        }
    }
}
