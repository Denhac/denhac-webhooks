<?php

namespace App\External;


use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

trait HasApiProgressBar
{
    public function apiProgress($title): ApiProgress
    {
        if (property_exists($this, 'output') && !is_null($this->output)) {
            return new class($this->output, $title) implements ApiProgress {
                private OutputInterface $output;
                private ProgressBar $bar;
                private int $current;
                private int $max;

                public function __construct(OutputInterface $output, string $title)
                {
                    $this->bar = new ProgressBar($output);

                    $this->bar->setFormat("$title: [%bar%] %percent:3s%% %current%/%max%");
                    $this->bar->display();
                    $this->output = $output;
                    $this->current = 0;
                    $this->max = 0;
                }

                function setProgress($current, $max = -1): void
                {
                    $this->current = $current;
                    if ($max == -1) {  // If it's not supplied, use whatever the last set max value was
                        $max = $this->max;
                    } else {
                        $this->max = $max;
                    }

                    $this->bar->setMaxSteps($max);
                    $this->bar->setProgress($current);
                    if ($current == $max) {
                        $this->bar->finish();
                        $this->output->write("\n");
                    }
                }

                function step(): void
                {
                    $this->setProgress($this->current + 1);
                }
            };
        }

        // No output, nothing to do
        return new class() implements ApiProgress {
            function setProgress($current, $max): void
            {
            }

            function step(): void
            {
            }
        };
    }
}
