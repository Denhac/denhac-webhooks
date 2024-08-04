<?php

namespace App\Issues;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ChoiceHelper
{
    private OutputInterface $output;

    private string $choiceText;

    private Collection $choices;

    public function __construct(OutputInterface $output, string $choiceText)
    {
        $this->choiceText = $choiceText;
        $this->output = $output;
        $this->choices = collect();
    }

    public function option($name, $callback): static
    {
        $this->choices->put($name, $callback);

        return $this;
    }

    /**
     * @return bool Whether the issue was fixed or not
     */
    public function run(): bool
    {
        if (! $this->choices->has('Cancel')) {
            $this->choices->put('Cancel', function () {
                return false;
            });
        }

        $question = new ChoiceQuestion($this->choiceText, $this->choices->keys()->toArray());
        $choiceResult = $this->output->askQuestion($question);

        if (empty($choiceResult)) {
            return false;
        }

        $runResult = $this->choices->get($choiceResult);
        if(is_null($runResult)) {
            return true;  // We assume a null just means it was run successfully as a lambda or something.
        } else if(is_bool($runResult)) {
            return $runResult;  // If the option run result was a boolean, we go with that option
        }

        return false;  // Anything else, we assume this wasn't fixed.
    }
}
