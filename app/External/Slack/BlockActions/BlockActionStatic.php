<?php

namespace App\External\Slack\BlockActions;

use App\Http\Requests\SlackRequest;

interface BlockActionStatic
{
    public static function blockId(): string;

    public static function actionId(): string;

    public static function handle(SlackRequest $request);
}
