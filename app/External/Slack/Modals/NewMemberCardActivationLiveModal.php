<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NewMemberCardActivationLiveModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    private string $infoHeading = 'This modal will change as their card goes through the various states of activation. Please keep it open until it tells you to scan the card to test or it times out trying to perform a particular step.';

    private string $activationFailedMessage = "The member's card may not be activated at this time. Please wait 10 minutes and if it's still not activated, an email will be sent to access@denhac.org and to the member on the next failed card scan.";

    private string $lockupProcedure = "Please make sure to go over how to lock up the space if they're the last one out.";

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Card Activation')
            ->clearOnClose(true)
            ->close('Close');
    }

    public function placeholder()
    {
        $this->modalView->newSection()
            ->mrkdwnText("This is a placeholder and should go away soon. If it hasn't after a few seconds, something broke. Oops.");

        return $this;
    }

    public function showSubmitted(int $timeout)
    {
        if ($timeout > 0) {
            $this->modalView->newSection()->mrkdwnText($this->infoHeading);

            $this->modalView->newSection()
                ->mrkdwnText("The website has been updated and this member's subscription is active. Please wait for the card to be sent for activation.");

            $this->modalView->newSection()
                ->mrkdwnText("This dialog will timeout in {$timeout} seconds.");
        } else {
            $this->modalView->newSection()
                ->mrkdwnText($this->activationFailedMessage);
        }
    }

    public function showCardSentForActivation(int $timeout)
    {
        if ($timeout > 0) {
            $this->modalView->newSection()->mrkdwnText($this->infoHeading);

            $this->modalView->newSection()
                ->mrkdwnText("The new member's card has been submitted for activation. Please wait while the card is being activated.");

            $this->modalView->newSection()
                ->mrkdwnText("This dialog will timeout in {$timeout} seconds.");
        } else {
            $this->modalView->newSection()
                ->mrkdwnText($this->activationFailedMessage);
        }
    }

    public function showCardActivated(int $timeout)
    {
        if ($timeout > 0) {
            $this->modalView->newSection()->mrkdwnText($this->infoHeading);

            $this->modalView->newSection()
                ->mrkdwnText("The member's card looks like it has been activated, but this can sometimes go wrong. Please scan the card on any denhac door to verify the card status.");

            $this->modalView->newSection()
                ->mrkdwnText("This dialog will timeout in {$timeout} seconds.");
        } else {
            $this->modalView->newSection()
                ->mrkdwnText($this->activationFailedMessage);
        }
    }

    public function showScanFailed()
    {
        $this->modalView->newSection()
            ->mrkdwnText('That card scan failed, unfortunately.');

        $this->modalView->newSection()
            ->mrkdwnText($this->activationFailedMessage);

        $this->modalView->newSection()->mrkdwnText($this->lockupProcedure);
    }

    public function showScanSuccess()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Hey, that worked! I mean... I knew it'd work the whole time...");

        $this->modalView->newSection()->mrkdwnText($this->lockupProcedure);
    }

    public static function callbackId(): string
    {
        return 'new-member-card-activation-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
