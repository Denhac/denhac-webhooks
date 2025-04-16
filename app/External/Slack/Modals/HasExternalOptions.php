<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Surfaces\OptionsResult;

trait HasExternalOptions
{
    abstract public static function getExternalOptions(SlackRequest $request): OptionsResult;
}
