<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Collections\OptionSet;

trait HasExternalOptions
{
    public abstract static function getExternalOptions(SlackRequest $request): OptionSet;
}
