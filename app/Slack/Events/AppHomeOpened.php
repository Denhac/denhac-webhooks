<?php

namespace App\Slack\Events;

use App\Actions\Slack\UpdateSpaceBotAppHome;
use App\Http\Requests\SlackRequest;

class AppHomeOpened implements EventInterface
{
    public static function eventType(): string
    {
        return 'app_home_opened';
    }

    public function handle(SlackRequest $request)
    {
        /** @var UpdateSpaceBotAppHome $updateSpaceBotAppHome */
        $updateSpaceBotAppHome = app(UpdateSpaceBotAppHome::class);
        $updateSpaceBotAppHome
            ->onQueue()
            ->execute($request->getSlackId());
    }
}
