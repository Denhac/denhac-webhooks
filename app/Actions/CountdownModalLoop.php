<?php

namespace App\Actions;

use App\Slack\Modals\CountdownTestModal;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class CountdownModalLoop
{
    use QueueableAction;

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($viewId)
    {
        $timeLeft = 30;
        while($timeLeft >= 0) {
            $view = new CountdownTestModal($timeLeft);
            $this->slackApi->views->update($viewId, $view);
            sleep(1);

            $timeLeft -= 1;
        }
    }
}
