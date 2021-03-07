<?php

namespace App\Slack\Events;

use App\Customer;
use App\Slack\SlackApi;
use Jeremeamia\Slack\BlockKit\Slack;
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
        $home->text("This is a test of SpaceBot app home");

        $user_id = $event['user'];
        /** @var Customer $member */
        $member = Customer::whereSlackId($user_id);

        if(is_null($member)) {
            $home->text("I don't recognize you, unfortunately");
        } else if(! $member->member) {
            $home->text("I do recognize you, but I don't see you as an active member");
        } else {
            $home->text("You're an active member! Thank you for being part of denhac!");
        }

        $this->slackApi->views_publish($user_id, $home);
    }

    public static function eventType(): string
    {
        return 'app_home_opened';
    }
}
