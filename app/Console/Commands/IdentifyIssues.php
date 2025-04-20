<?php

namespace App\Console\Commands;

use App\Issues\Fixing\Fixable;
use App\Issues\Fixing\ICanFixThem;
use App\Issues\IssueChecker;
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
            $canFixThisIssueType = $firstIssue instanceof Fixable;
            $canFixAutomatically = $firstIssue instanceof ICanFixThem;

            $this->info(sprintf('%d: %s (%d)', $issueNumber, $issueTitle, count($myIssues)));
            $this->info("URL: {$firstIssue->getIssueURL()}");
            if ($canFixThisIssueType) {
                $automatedText = $canFixAutomatically ? "They will be fixed automatically." : "";
                $this->info("These issues can be fixed by this tool. $automatedText");
            }
            foreach ($myIssues as $issue) {
                /** @var IssueBase $issue */
                $this->info("\t{$issue->getIssueText()}");
                if ($canFixThisIssueType) {
                    /** @var Fixable|IssueBase $issue */
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
            /** @var IssueBase|Fixable $issue */
            $this->info(sprintf('%d: %s', $issue::getIssueNumber(), $issue->getIssueText()));
            try {
                $wasFixed = $issue->fix();
                if ($wasFixed) {
                    $numIssuesFixed++;
                }

                if($issue instanceof ICanFixThem) {
                    if($wasFixed) {
                        $this->info("This issue was automatically fixed.");
                    } else {
                        $this->info("This issue failed to be fixed automatically.");
                    }
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
