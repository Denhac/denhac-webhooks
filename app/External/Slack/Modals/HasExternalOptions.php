<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Surfaces\OptionsResult;

trait HasExternalOptions
{
    public abstract static function getExternalOptions(SlackRequest $request): OptionsResult;
}
