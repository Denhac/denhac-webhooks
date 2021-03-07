<?php

namespace App\Slack\Events;

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

        $this->slackApi->views_publish($event['user'], $home);
    }

    public static function eventType(): string
    {
        return 'app_home_opened';
    }
}
