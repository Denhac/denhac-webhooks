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
        "This is a community. You are a member, not a customer. This organization runs on the contributions of its members.",
        "We're a drug and alcohol free space. There is hazardous equipment here, always keep everyone's safety in mind.",
        "Don't live or sleep at the space. That includes in the parking lot. Don't leave your car in the lot overnight.",
        "There are other tenants in the building. Don't go upstairs except for the gender neutral bathroom since there's not one on the first floor. Close denhac doors if no one else is in the room. The exterior doors are unlocked randomly throughout the day.",
        "Slack has many channels you can join. Channels prefixed with help- are a good place to start to ask for training on equipment. Channels prefixed with sig- are a good place to start volunteering and get involved. Reply in threads to make channels easier to follow.",
        "Read all signs everywhere and abide by them. Also read the wiki. If something isn't in the wiki, add it. Check out the How To page on our website, it provides some information on 'How to be a denhac member' essentially.",
        "No Harassment or Bullying: This includes, but is not limited to, attention that comes after a request to stop. Recognize and respect everyone's differences. Treat all members with kindness and respect. Our anti-harassment policy is part of our member agreement - please read it.",
        "The last Saturday of the month is Hack denhac Day. The space is closed for personal project work. Come help us work on the space.",
        "Leave the space better than you found it. Take out the trash. Communicate any issues through Slack.",
    );

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Member Info')
            ->clearOnClose(true)
            ->clearOnClose('Close');

        foreach (self::INFO as $info) {
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
