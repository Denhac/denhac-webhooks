<?php

namespace App\Actions;

use App\External\Slack\Modals\NewMemberCardActivationLiveModal;
use App\External\Slack\SlackApi;
use App\NewMemberCardActivation;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class NewMemberCardSlackLiveView
{
    use QueueableAction;

    public string $queue = 'slack-live';

    private SlackApi $api;

    public function __construct(SlackApi $api)
    {
        $this->api = $api;
    }

    public function execute($viewId, NewMemberCardActivation $newMemberCardActivation)
    {
        $currentState = $newMemberCardActivation->state;
        $timeout = 120;

        while (true) {
            $updatedModal = new NewMemberCardActivationLiveModal();

            if ($currentState == NewMemberCardActivation::SUBMITTED) {
                $updatedModal->showSubmitted($timeout);
            } elseif ($currentState == NewMemberCardActivation::CARD_SENT_FOR_ACTIVATION) {
                $updatedModal->showCardSentForActivation($timeout);
            } elseif ($currentState == NewMemberCardActivation::CARD_ACTIVATED) {
                $updatedModal->showCardActivated($timeout);
            } elseif ($currentState == NewMemberCardActivation::SCAN_FAILED) {
                $updatedModal->showScanFailed();
                Log::info("NMA Failed for {$newMemberCardActivation->wooCustomerId}");
                $timeout = 0;
            } elseif ($currentState == NewMemberCardActivation::SUCCESS) {
                $updatedModal->showScanSuccess();
                Log::info("NMA Success for {$newMemberCardActivation->wooCustomerId}");
                $timeout = 0;
            }

            $this->api->views->update($viewId, $updatedModal); // TODO check that the modal isn't closed already.

            if ($timeout == 0) {
                // $newMemberCardActivation->delete();
                return;
            }

            $timeout -= 1;
            sleep(1);
            $newMemberCardActivation->refresh();
            if ($currentState != $newMemberCardActivation->state) {
                $currentState = $newMemberCardActivation->state;

                if ($currentState == NewMemberCardActivation::CARD_ACTIVATED) {
                    $timeout = 300;
                } else {
                    $timeout = 120;
                }
            }
        }
    }
}
