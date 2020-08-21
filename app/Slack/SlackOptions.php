<?php

namespace App\Slack;


use Jeremeamia\Slack\BlockKit\Element;
use Jeremeamia\Slack\BlockKit\Partials\HasOptions;

class SlackOptions extends Element
{
    use HasOptions;

    /**
     * @return SlackOptions
     */
    public static function new() {
        return new SlackOptions();
    }

    public function jsonSerialize()
    {
        return $this->getOptionsAsArray();
    }

    public function validate(): void
    {
        $this->validateOptions();
    }
}
