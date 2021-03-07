<?php

namespace App\Slack\Events;

use App\Customer;
use App\Slack\CommonResponses;
use App\Slack\SlackApi;
use App\UserMembership;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\AppHome;
use Spatie\QueueableAction\QueueableAction;

class AppHomeOpened implements EventInterface
{
    use QueueableAction;

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     *
     * @param $event
     * @return void
     */
    public function execute($event)
    {
        $home = Slack::newAppHome();

        $user_id = $event['user'];
        /** @var Customer $member */
        $member = Customer::whereSlackId($user_id)->first();

        if(is_null($member)) {
            $home->text(CommonResponses::unrecognizedUser());
        } else if(! $member->member) {
            $home->text(CommonResponses::notAMemberInGoodStanding());
        } else {
            $this->activeMember($home, $member);
        }

        $this->slackApi->views_publish($user_id, $home);
    }

    private function activeMember(AppHome $home, Customer $member)
    {
        $home->text("You're an active member! Thank you for being part of denhac!");

        if($member->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            $home->text("You are authorized to use the 3d printers");
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_3DP_TRAINER)) {
            $home->text("You are authorized to train others to use the 3d printers");
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_USER)) {
            $home->text("You are authorized to use the laser cutter");
        }

        if($member->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_TRAINER)) {
            $home->text("You are authorized to train others to use the laser cutter");
        }
    }

    public static function eventType(): string
    {
        return 'app_home_opened';
    }
}
