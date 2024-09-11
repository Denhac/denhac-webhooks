<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NewMemberInfoModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    private const INFO = array(
        "Don't live or sleep at the space. That includes in the parking lot. Don't leave your car in the lot overnight.",
        "We're a drug and alcohol free space.",
        "There are other tenants in the building. Don't go upstairs except for the gender neutral bathroom since there's not one on the first floor. Close denhac doors if no one else is in the room. The exterior doors are unlocked randomly throughout the day.",
        "Slack has many channels you can join. Channels prefixed with help- are a good place to start to ask for training on equipment. Reply in threads to make channels easier to follow.",
        "Read all signs everywhere and abide by them. Also read the wiki. If something isn't in the wiki, add it.",
        "The last Saturday of the month is Hack denhac Day. The space is closed for personal project work. Come help.",
        "This is a community. You are not a customer, you are a member. Leave the space better than you found it. Take out the trash. Communicate any issues through Slack.",
    );

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Member Info')
            ->clearOnClose(true)
            ->clearOnClose('Close');

        foreach ($this->INFO as $info) {
            $this->modalView->newSection()
                ->mrkdwnText($info);
        }
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
