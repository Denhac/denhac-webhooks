<?php

namespace App\Issues;

use App\Issues\Fixing\Preamble;
use Illuminate\Support\Collection;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class ChoiceHelper
{
    public const CANCEL = "Cancel";

    private string $choiceText;

    private Collection $choices;
    private string $defaultChoice;

    private ?Preamble $preamble = null;

    public function __construct(string $choiceText)
    {
        $this->choiceText = $choiceText;
        $this->choices = collect();
        $this->defaultChoice = self::CANCEL;
    }

    public function preamble(string|Preamble $preamble): static
    {
        if (is_string($preamble)) {
            $this->preamble = new class($preamble) extends Preamble {
                public function __construct(private $message)
                {
                }

                public function preamble(): void
                {
                    info($this->message);
                }
            };
        } else {
            $this->preamble = $preamble;
        }

        return $this;
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
        if(! $this->choices->has(self::CANCEL)) {
            $cancelChoice = function () {
                return false;
            };

            $this->choices->put(self::CANCEL, $cancelChoice);
        }

        if (! is_null($this->preamble)) {
            $this->preamble->preamble();
        }

        $choiceResult = select(
            label: $this->choiceText,
            options: $this->choices->keys()->toArray(),
            default: $this->defaultChoice
        );

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
