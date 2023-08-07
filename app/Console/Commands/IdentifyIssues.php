<?php

namespace App\Console\Commands;

use App\Issues\IssueChecker;
use Illuminate\Console\Command;

class IdentifyIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:identify-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies issues with membership and access';

    /**
     * Generate and print out all of our issues
     */
    public function handle(): void
    {
        $this->info('Identifying issues');

        /** @var IssueChecker $issueChecker */
        $issueChecker = app(IssueChecker::class);
        $issueChecker->setOutput($this->output);

        $issues = $issueChecker->getIssues();
        $this->info("There are {$issues->count()} total issues.");
        $this->info('');

        foreach ($issues->keys() as $issueKey) {
            $this->info($issueKey);
            foreach ($issues->get($issueKey) as $issue) {
                $this->info($issue);
            }
            $this->info('');
        }
    }
}
