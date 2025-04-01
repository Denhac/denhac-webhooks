<?php

namespace App\Issues;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ChoiceHelper
{
    public const CANCEL = "Cancel";

    private OutputInterface $output;

    private string $choiceText;

    private Collection $choices;
    private string $defaultChoice;

    public function __construct(OutputInterface $output, string $choiceText)
    {
        $this->choiceText = $choiceText;
        $this->output = $output;
        $this->choices = collect();
        $cancelChoice = function () {
            return false;
        };

        $this->defaultChoice = self::CANCEL;
        $this->choices->put(self::CANCEL, $cancelChoice);
    }

    public function option($name, $callback): static
    {
        $this->choices->put($name, $callback);

        return $this;
    }

    public function defaultOption($name, $callback): static
    {
        $this->defaultChoice = $name;

        return $this->option($name, $callback);
    }

    /**
     * @return bool Whether the issue was fixed or not
     */
    public function run(): bool
    {
        $question = new ChoiceQuestion(
            $this->choiceText,
            $this->choices->keys()->toArray(),
            $this->defaultChoice
        );
        $choiceResult = $this->output->askQuestion($question);

        if (empty($choiceResult)) {
            return false;
        }

        $callback = $this->choices->get($choiceResult);
        $runResult = $callback();
        if (is_null($runResult)) {
            return true;  // We assume a null just means it was run successfully as a lambda or something.
        } elseif (is_bool($runResult)) {
            return $runResult;  // If the option run result was a boolean, we go with that option
        }

        return false;  // Anything else, we assume this wasn't fixed.
    }
}
