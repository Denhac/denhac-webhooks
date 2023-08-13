<?php

namespace App\Issues;


use App\Issues\Checkers\IssueCheck;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class IssueChecker
{
    protected Collection $checkers;
    protected Collection|null $issues = null;
    private IssueData $issueData;
    private OutputInterface|null $output = null;

    public function __construct()
    {
        $this->issueData = app(IssueData::class);
        app()->instance(IssueData::class, $this->issueData);

        $this->checkers = collect(get_declared_classes())
            ->filter(fn($name) => str_starts_with($name, 'App\\Issues\\Checkers'))
            ->map(fn($name) => new ReflectionClass($name))
            ->filter(fn($reflect) => $reflect->implementsInterface(IssueCheck::class))
            ->map(fn($reflect) => $reflect->getName())
//            ->filter(fn($name) => $name == GitHubIssues::class)  // Uncomment/change class name to test one checker
            ->map(fn($name) => app($name));
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
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
                if (!is_null($this->output)) {
                    $shortName = Str::replace('App\\Issues\\Checkers\\', '', get_class($checker));
                    $this->output->writeln("Getting issues for: $shortName");
                }
                $this->issues = $this->issues->concat($checker->getIssues());
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
