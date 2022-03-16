<?php

namespace App\Actions;

use App\NewMemberCardActivation;
use App\Slack\Modals\NewMemberCardActivationLiveModal;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class NewMemberCardSlackLiveView
{
    use QueueableAction;

    public string $queue = 'no-timeout';

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
            } else if ($currentState == NewMemberCardActivation::CARD_SENT_FOR_ACTIVATION) {
                $updatedModal->showCardSentForActivation($timeout);
            } else if ($currentState == NewMemberCardActivation::CARD_ACTIVATED) {
                $updatedModal->showCardActivated($timeout);
            } else if ($currentState == NewMemberCardActivation::SCAN_FAILED) {
                $updatedModal->showScanFailed();
                $timeout = 0;
            } else if ($currentState == NewMemberCardActivation::SUCCESS) {
                $updatedModal->showScanSuccess();
                $timeout = 0;
            }

            if ($timeout == 0) {
                $newMemberCardActivation->delete();
                return;
            }

            $this->api->views->update($viewId, $updatedModal); // TODO check that the modal isn't closed already.

            $timeout -= 1;
            sleep(1);
            $newMemberCardActivation->refresh();
            if ($currentState != $newMemberCardActivation->state) {
                $currentState = $newMemberCardActivation->state;

                if ($currentState == NewMemberCardActivation::CARD_SENT_FOR_ACTIVATION) {
                    $timeout = 120;
                } else if ($currentState == NewMemberCardActivation::CARD_ACTIVATED) {
                    $timeout = 300;
                }
            }
        }
    }
}
