<?php

namespace Tests\Helpers;


use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsIdentical;
use Spatie\QueueableAction\ActionJob;

class ActionAssertion
{
    private Collection $jobs;

    public function __construct(Collection|ActionJob $jobs)
    {
        if($jobs instanceof ActionJob) {
            $jobs = collect([$jobs]);
        }
        $this->jobs = $jobs;
    }

    public function with(...$args)
    {
        // Shortcut if there's only one job to get better test failure info
        if($this->jobs->count() == 1) {
            Assert::assertSame($args, $this->jobs->first()->parameters());
            return;
        }

        $matchingJobs = $this->jobs
            ->filter(function($job) use ($args) {
                /** @var ActionJob $job */
                $parameters = $job->parameters();

                $identical = new IsIdentical($args);

                return $identical->evaluate($parameters, '', true);
            });

        Assert::assertNotEquals(0, $matchingJobs->count(), "No matching action was queued");
    }
}
