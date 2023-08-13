<?php

namespace App\External\Slack\BlockActions;


use App\Http\Requests\SlackRequest;

interface BlockActionInterface
{
    public function blockId(): string;

    public function actionId(): string;

    public function handle(SlackRequest $request);
}
