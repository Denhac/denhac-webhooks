<?php

namespace App\Issues;


use App\Issues\Checkers\IssueCheck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class IssueChecker
{
    protected Collection $checkers;
    protected MessageBag|null $issues;

    public function __construct(OutputInterface|null $output = null)
    {
        $this->issues = null;
        /** @var IssueData $issueData */
        $issueData = app(IssueData::class);
        $issueData->setOutput($output);
        app()->instance(IssueData::class, $issueData);

        $this->checkers = collect(get_declared_classes())
            ->filter(fn($name) => str_starts_with($name, 'App\\Issues\\Checkers'))
            ->map(fn($name) => new ReflectionClass($name))
            ->filter(fn($reflect) => $reflect->implementsInterface(IssueCheck::class))
            ->map(fn($reflect) => $reflect->getName())
            ->map(fn($name) => app($name));
    }

    public function getIssues(): MessageBag
    {
        if (is_null($this->issues)) {
            $this->issues = new MessageBag();

            foreach ($this->getIssueCheckers() as $checker) {
                /** @var IssueCheck $checker */
                foreach ($checker->getIssues() as $issue) {
                    $this->issues->add($checker->issueTitle(), $issue);
                }
            }
        }

        return $this->issues;
    }

    public function getIssueCheckers(): Collection
    {
        return $this->checkers;
    }
}
