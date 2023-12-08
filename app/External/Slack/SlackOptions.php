<?php

namespace App\External\Slack;

use SlackPhp\BlockKit\Element;
use SlackPhp\BlockKit\Partials\HasOptions;
use SlackPhp\BlockKit\Partials\Option;

class SlackOptions extends Element
{
    use HasOptions;

    /**
     * @return SlackOptions
     */
    public static function new(): SlackOptions
    {
        return new self();
    }

    public function jsonSerialize()
    {
        return $this->getOptionsAsArray();
    }

    public function validate(): void
    {
        $this->validateOptions();
    }

    public function filterByValue($value)
    {
        if (empty($value)) {
            return;
        }

        $value = strtolower($value);
        $this->options = array_filter($this->options, function ($option) use ($value) {
            /** @var Option $option */

            // This is the only way to get the text currently
            $optionArray = $option->toArray();
            $text = strtolower($optionArray['text']['text']);

            return strpos($text, $value) === false ? false : true;
        });
    }
}
