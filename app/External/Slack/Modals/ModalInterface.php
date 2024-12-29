<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Collections\OptionSet;

interface ModalInterface extends \JsonSerializable
{
    public static function callbackId(): string;

    public static function handle(SlackRequest $request): JsonResponse;

    public static function getOptions(SlackRequest $request): OptionSet;
}
