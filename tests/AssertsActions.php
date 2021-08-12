<?php

namespace Tests;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Spatie\QueueableAction\ActionJob;
use Tests\Helpers\ActionAssertion;

trait AssertsActions
{
    /**
     * @var Collection
     */
    private Collection $actionAssertions;

    protected function setUpActionAssertion() {
        $this->actionAssertions = collect();
        $this->beforeApplicationDestroyed(fn () => $this->checkAssertions());
    }

    private function checkAssertions() {
        $this->actionAssertions->each(function($assertion) {
            /** @var ActionAssertion $assertion */

            $assertion->check();
        });
    }

    public function assertAction($cls): ActionAssertion
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $jobs = Queue::pushedJobs();
        if(array_key_exists(ActionJob::class, $jobs)) {
            $actionJobs = collect($jobs[ActionJob::class]);
        } else {
            $actionJobs = collect();
        }

        $actionAssertion = new ActionAssertion($cls, $actionJobs, $backtrace);
        $this->actionAssertions->push($actionAssertion);
        return $actionAssertion;
    }
}
