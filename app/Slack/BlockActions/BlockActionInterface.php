<?php

namespace App\Slack\BlockActions;


use App\Http\Requests\SlackRequest;

interface BlockActionInterface
{
    public static function blockId(): string;

    public static function actionId(): string;

    public static function handle(SlackRequest $request);
}
