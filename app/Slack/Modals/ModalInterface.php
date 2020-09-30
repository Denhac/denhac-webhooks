<?php

namespace App\Slack\Modals;

use App\Http\Requests\SlackRequest;

interface ModalInterface extends \JsonSerializable
{
    public static function callbackId();

    public static function handle(SlackRequest $request);

    public static function getOptions(SlackRequest $request);
}
