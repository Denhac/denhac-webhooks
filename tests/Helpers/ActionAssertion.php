<?php

namespace Tests\Helpers;


use PHPUnit\Framework\Assert;
use Spatie\QueueableAction\ActionJob;

class ActionAssertion
{
    private ActionJob $job;

    public function __construct($job)
    {
        $this->job = $job;
    }

    public function with(...$args)
    {
        Assert::assertSame($args, $this->job->parameters());
    }
}
