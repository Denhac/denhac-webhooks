<?php

namespace App\Jobs;

use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleSlackEventChannelCreated implements ShouldQueue
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
        $channelId = $this->event["channel"]["id"];

        $slackApi->conversations_join($channelId);
    }
}
