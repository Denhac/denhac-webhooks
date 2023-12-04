<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NewMemberInfoModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Member Info')
            ->clearOnClose(true)
            ->clearOnClose('Close');

        $this->modalView->newSection()
            ->mrkdwnText("1. Don't live at/sleep at the space. That includes in the parking lot. Don't leave your car in the lot overnight.");
        $this->modalView->newSection()
            ->mrkdwnText("2. We're a drug and alcohol free space.");
        $this->modalView->newSection()
            ->mrkdwnText("3. There are other tenants in the building. Don't go upstairs except for the gender neutral bathroom since there's not one on the first floor. Close denhac doors if no one else is in the room.");
        $this->modalView->newSection()
            ->mrkdwnText("4. Our Slack instance has many channels, you'll only be in a few by default. Channel prefixes like help- are a good place to start to ask for training on equipment.");
        $this->modalView->newSection()
            ->mrkdwnText('5. Leave the space better than you found it. Communicate any issues through Slack.');
    }

    public static function callbackId(): string
    {
        return 'new-member-info-modal';
    }

    public static function handle(SlackRequest $request)
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
