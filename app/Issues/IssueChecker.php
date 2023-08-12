<?php

namespace App\Issues;


use App\Issues\Checkers\IssueCheck;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class IssueChecker
{
    protected Collection $checkers;
    protected Collection|null $issues = null;
    private IssueData $issueData;

    public function __construct()
    {
        $this->issueData = app(IssueData::class);
        app()->instance(IssueData::class, $this->issueData);

        $this->checkers = collect(get_declared_classes())
            ->filter(fn($name) => str_starts_with($name, 'App\\Issues\\Checkers'))
            ->map(fn($name) => new ReflectionClass($name))
            ->filter(fn($reflect) => $reflect->implementsInterface(IssueCheck::class))
            ->map(fn($reflect) => $reflect->getName())
            ->map(fn($name) => app($name));
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->issueData->setOutput($output);
    }

    /**
     * @return Collection<IssueBase>
     */
    public function getIssues(): Collection
    {
        if (is_null($this->issues)) {
            $this->issues = collect();

            foreach ($this->getIssueCheckers() as $checker) {
                $this->issues = $this->issues->union($checker->getIssues());
            }
        }

        return $this->issues;
    }

    /**
     * @return Collection<IssueCheck>
     */
    public function getIssueCheckers(): Collection
    {
        return $this->checkers;
    }
}
