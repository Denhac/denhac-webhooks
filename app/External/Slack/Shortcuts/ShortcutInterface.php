<?php

namespace App\External\Slack\Shortcuts;

use App\Http\Requests\SlackRequest;

interface ShortcutInterface
{
    public static function callbackId(): string;

    public static function handle(SlackRequest $request);
}
