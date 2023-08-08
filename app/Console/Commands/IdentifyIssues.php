<?php

namespace App\Console\Commands;

use App\Issues\IssueChecker;
use App\Issues\Types\IssueBase;
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

        $allIssues = $issueChecker->getIssues();
        $this->info("There are {$allIssues->count()} total issues.");
        $this->info('');

        $issuesByNumber = $allIssues->groupBy(fn($i) => $i->getIssueNumber());

        foreach ($issuesByNumber->keys() as $issueNumber) {
            $myIssues = $issuesByNumber->get($issueNumber);
            $issueCount = count($myIssues);
            /** @var IssueBase $firstIssue */
            $firstIssue = $myIssues->first();
            $issueTitle = $firstIssue->getIssueTitle();

            $this->info(sprintf("%04d: %s (%d)", $issueNumber, $issueTitle, count($myIssues)));
            $this->info("URL: {$firstIssue->getIssueURL()}");
            foreach ($myIssues as $issue) {
                /** @var IssueBase $issue */
                $this->info("\t{$issue->getIssueText()}");
            }
            $this->info('');
        }
    }
}
