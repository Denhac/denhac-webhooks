<?php

namespace App\Jobs;

use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleSlackEventMemberJoinedChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event;

    /**
     * Create a new job instance.
     *
     * @param $event
     */
    public function __construct($event)
    {
        $this->event = $event;
        $this->onQueue("slack");
    }

    /**
     * Execute the job.
     *
     * @param SlackApi $slackApi
     * @return void
     */
    public function handle(SlackApi $slackApi)
    {
        $userId = $this->event["user"];
        $channelId = $this->event["channel"];

        if($userId == "UNEA0SKK3") {
            $slackApi->conversations_kick($userId, $channelId);
        }
    }
}
