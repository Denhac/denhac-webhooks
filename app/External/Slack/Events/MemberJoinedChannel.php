<?php

namespace App\External\Slack\Events;


use App\Actions\Slack\RemoveFromChannel;
use App\Http\Requests\SlackRequest;
use App\TempBan;
use Illuminate\Support\Facades\Log;

class MemberJoinedChannel implements EventInterface
{
    public static function eventType(): string
    {
        return 'member_joined_channel';
    }

    public function handle(SlackRequest $request)
    {
        $event = $request->event();
        $userId = $event['user'];
        $channelId = $event['channel'];

        if(TempBan::isBanned($userId, $channelId)) {
            Log::info("Kicked slack id {$userId} from {$channelId}");
            app(RemoveFromChannel::class)->execute($userId, $channelId);
        }
    }
}
