<?php

namespace Tests\Helpers;


use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\IsIdentical;
use Spatie\QueueableAction\ActionJob;
use function Spatie\SslCertificate\starts_with;

class ActionAssertion
{
    private const CONSTRAINT_TIMES_TYPE = 'times_type';
    private const TIMES_AT_LEAST = 'times_at_least';
    private const TIMES_AT_MOST = 'times_at_most';
    private const TIMES_EXACTLY = 'times_exactly';
    private const CONSTRAINT_TIMES_VALUE = 'times_value';
    private const CONSTRAINT_ARGS = 'args';

    private string $cls;
    private Collection $jobs;
    private array $constraints = [];
    private array $backtrace;

    public function __construct($cls, Collection|ActionJob $jobs, array $backtrace)
    {
        $this->cls = $cls;
        if ($jobs instanceof ActionJob) {
            $jobs = collect([$jobs]);
        }
        $this->jobs = $jobs;
        $this->once();
        $this->backtrace = $backtrace;
    }

    public function check()
    {
        // Filter by matching arguments
        $args = null;
        if (array_key_exists(self::CONSTRAINT_ARGS, $this->constraints)) {
            $args = $this->constraints[self::CONSTRAINT_ARGS];
        }

        $matchingJobs = $this->jobs
            ->map(fn($actionJob) => $actionJob['job'])
            ->filter(fn($actionJob) => $actionJob->displayName() == $this->cls)
            ->filter(function ($job) use ($args) {
                /** @var ActionJob $job */
                if (is_null($args)) { // Anything goes
                    return true;
                }

                $parameters = $job->parameters();

                $identical = new IsIdentical($args);

                return $identical->evaluate($parameters, '', true);
            });

        // Check the number of times it was queued
        $queueCount = $matchingJobs->count();

        $timeType = $this->constraints[self::CONSTRAINT_TIMES_TYPE];
        $timeValue = $this->constraints[self::CONSTRAINT_TIMES_VALUE];

        $timePluralValue = Str::plural("time", $timeValue);
        $timePluralQueueCount = Str::plural("time", $queueCount);
        try {
            if ($timeType == self::TIMES_EXACTLY) {
                $failureMessage = "Expected action to be queued exactly $timeValue $timePluralValue, "
                    . "but it was queued $queueCount $timePluralQueueCount.";
                Assert::assertEquals($timeValue, $queueCount, $failureMessage);
            } elseif ($timeType == self::TIMES_AT_LEAST) {
                $failureMessage = "Expected action to be queued at least $timeValue $timePluralValue, "
                    . "but it was queued $queueCount $timePluralQueueCount.";
                Assert::assertGreaterThanOrEqual($timeValue, $queueCount, $failureMessage);
            } elseif ($timeType == self::TIMES_AT_MOST) {
                $failureMessage = "Expected action to be queued at most $timeValue $timePluralValue, "
                    . "but it was queued $queueCount $timePluralQueueCount.";
                Assert::assertLessThanOrEqual($timeValue, $queueCount, $failureMessage);
            }
        } catch (AssertionFailedError $exception) {
            $this->printBacktrace();
            throw $exception;
        }
    }

    private function printBacktrace()
    {
        print("Assertion stacktrace:".PHP_EOL);
        $rootDir = dirname(app_path());
        foreach ($this->backtrace as $trace) {
            $filePath = $trace['file'];
            $relativeDir = substr($filePath, strlen($rootDir));
            if (starts_with($relativeDir, "/vendor")) {
                break;
            }

            print(' file://' . $filePath . ':' . $trace['line'] . PHP_EOL);
        }
    }

    public function with(...$args): static
    {
        $this->constraints[self::CONSTRAINT_ARGS] = $args;

        return $this;
    }

    public function never(): static
    {
        return $this->times(0);
    }

    public function once(): static
    {
        return $this->times(1);
    }

    public function times($times): static
    {
        return $this->timeConstraint(self::TIMES_EXACTLY, $times);
    }

    public function atMostOnce(): static
    {
        return $this->atMost(1);
    }

    public function atMost($times): static
    {
        return $this->timeConstraint(self::TIMES_AT_MOST, $times);
    }

    public function atLeastOnce(): static
    {
        return $this->atLeast(1);
    }

    public function atLeast($times): static
    {
        return $this->timeConstraint(self::TIMES_AT_LEAST, $times);
    }

    private function timeConstraint($type, $value): static
    {
        $this->constraints[self::CONSTRAINT_TIMES_TYPE] = $type;
        $this->constraints[self::CONSTRAINT_TIMES_VALUE] = $value;

        return $this;
    }
}
