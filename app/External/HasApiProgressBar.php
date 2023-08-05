<?php

namespace App\External;


use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

trait HasApiProgressBar
{
    public function apiProgress($title): ApiProgress
    {
        if (property_exists($this, 'output') && ! is_null($this->output)) {
            return new class($this->output, $title) implements ApiProgress {
                private ProgressBar $bar;

                public function __construct(OutputInterface $output, string $title)
                {
                    $this->bar = new ProgressBar($output);

                    $this->bar->setFormat("$title: [%bar%] %percent:3s%%");
                    $this->bar->display();
                }

                function setProgress($current, $max): void
                {
                    $this->bar->setMaxSteps($max);
                    $this->bar->setProgress($current);
                }
            };
        }

        // No output, nothing to do
        return new class() implements ApiProgress {
            function setProgress($current, $max): void
            {
            }
        };
    }
}
