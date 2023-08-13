<?php

namespace App\External\Slack\Events;


use App\Http\Requests\SlackRequest;

interface EventInterface
{
    public static function eventType(): string;

    public function handle(SlackRequest $request);
}
