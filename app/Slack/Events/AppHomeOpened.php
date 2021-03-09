<?php

namespace App\Slack\Events;

use App\Actions\UpdateSpaceBotAppHome;

class AppHomeOpened implements EventInterface
{
    public static function eventType(): string
    {
        return 'app_home_opened';
    }

    public static function handle($event)
    {
        /** @var UpdateSpaceBotAppHome $updateSpaceBotAppHome */
        $updateSpaceBotAppHome = app(UpdateSpaceBotAppHome::class);
        $updateSpaceBotAppHome
            ->onQueue()
            ->execute($event['user']);
    }
}
