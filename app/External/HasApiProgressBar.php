<?php

namespace App\External;

use Laravel\Prompts\Concerns\Themes;
use Laravel\Prompts\Progress;
use Laravel\Prompts\Prompt;
use function Laravel\Prompts\progress;

trait HasApiProgressBar
{
    public function apiProgress($title): ApiProgress
    {
        return new class($title) implements ApiProgress {
            private Progress $bar;

            public function __construct(string $title)
            {
                $this->bar = progress(
                    label: $title,
                    steps: 1
                );
            }

            public function setProgress($current, $max = null): void
            {
                // If $max is supplied, update our total progress
                if (! is_null($max)) {
                    $this->bar->total = $max;
                }

                // We don't want to go past our end.
                $this->bar->progress = min($current, $this->bar->total);
                $this->bar->render();
                if ($current == $max) {
                    $this->bar->finish();
                }
            }

            public function step(): void
            {
                $this->setProgress($this->bar->progress + 1);
            }
        };

//        // No output, nothing to do
//        return new class implements ApiProgress
//        {
//            public function setProgress($current, $max): void {}
//
//            public function step(): void {}
//        };
    }
}
