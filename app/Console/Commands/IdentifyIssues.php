<?php

namespace App\Console\Commands;

use App\Issues\IssueChecker;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

        $allIssues = $issueChecker->getIssues();
        $this->info("There are {$allIssues->count()} total issues.");
        $this->newLine();

        $issuesByNumber = $allIssues->groupBy(fn ($i) => $i->getIssueNumber());
        $sortedIssueNumbers = $issuesByNumber->keys()->sort();

        $fixableIssues = collect();

        foreach ($sortedIssueNumbers as $issueNumber) {
            $myIssues = $issuesByNumber->get($issueNumber);
            /** @var IssueBase $firstIssue */
            $firstIssue = $myIssues->first();
            $issueTitle = $firstIssue->getIssueTitle();
            $issueTraits = (new \ReflectionClass($firstIssue))->getTraits();
            $canFixThisIssueType = array_key_exists(ICanFixThem::class, $issueTraits);

            $this->info(sprintf('%d: %s (%d)', $issueNumber, $issueTitle, count($myIssues)));
            $this->info("URL: {$firstIssue->getIssueURL()}");
            if ($canFixThisIssueType) {
                $this->info('These issues can be fixed by this tool.');
            }
            foreach ($myIssues as $issue) {
                /** @var IssueBase $issue */
                $this->info("\t{$issue->getIssueText()}");
                if ($canFixThisIssueType) {
                    /** @var ICanFixThem|IssueBase $issue */
                    $fixableIssues->add($issue);
                }
            }
            $this->newLine();
        }

        $fixableIssueCount = $fixableIssues->count();
        $issueOrIssues = Str::plural('issue', $fixableIssueCount);
        $this->info("We have $fixableIssueCount $issueOrIssues we can fix.");

        if ($fixableIssueCount == 0) {
            return;
        }

        if (! $this->confirm('Would you like to fix these now?')) {
            return;
        }

        $numIssuesFixed = 0;
        foreach ($fixableIssues as $issue) {
            /** @var IssueBase|ICanFixThem $issue */
            $this->info(sprintf('%d: %s', $issue::getIssueNumber(), $issue->getIssueText()));
            try {
                if ($issue->fix()) {
                    $numIssuesFixed++;
                }
            } catch (\Exception $exception) {
                $this->getApplication()->renderThrowable($exception, $this->output->getOutput());
            }
            $this->newLine();
        }

        $fixedIssueOrIssues = Str::plural('issue', $numIssuesFixed);
        $this->newLine();
        $this->info("We fixed $numIssuesFixed $fixedIssueOrIssues out of $fixableIssueCount $issueOrIssues");
    }
}
