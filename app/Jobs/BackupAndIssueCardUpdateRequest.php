<?php

namespace App\Jobs;

use App\CardUpdateRequest;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\WinDSX\BackupWinDSX;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BackupAndIssueCardUpdateRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CardSentForActivation|CardSentForDeactivation
     */
    private $cardSentForRequest;

    /**
     * Create a new job instance.
     *
     * @param CardSentForActivation|CardSentForDeactivation $cardSentForRequest
     */
    public function __construct($cardSentForRequest)
    {
        $this->cardSentForRequest = $cardSentForRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $path = storage_path("backups/on_cards/" . date("Y/m/d/h/i"));
        /** @var BackupWinDSX $backupWinDSX */
        $backupWinDSX = app(BackupWinDSX::class);
        $backupWinDSX->backup($path);

        if($this->cardSentForRequest instanceof CardSentForActivation) {
            CardUpdateRequest::create([
                "type" => CardUpdateRequest::ACTIVATION_TYPE,
                "customer_id" => $this->cardSentForRequest->wooCustomerId,
                "card" => $this->cardSentForRequest->cardNumber,
            ]);
        } else if($this->cardSentForRequest instanceof CardSentForDeactivation) {
            CardUpdateRequest::create([
                "type" => CardUpdateRequest::DEACTIVATION_TYPE,
                "customer_id" => $this->cardSentForRequest->wooCustomerId,
                "card" => $this->cardSentForRequest->cardNumber,
            ]);
        }
        // TODO Throw an error if it's not one of those
    }
}
