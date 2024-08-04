<?php

namespace App\Issues;

use App\Issues\Checkers\IssueCheck;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class IssueChecker
{
    protected Collection $checkers;

    protected ?Collection $issues = null;

    private IssueData $issueData;

    private ?OutputStyle $output = null;

    public function __construct()
    {
        $this->issueData = app(IssueData::class);
        app()->instance(IssueData::class, $this->issueData);

        $this->checkers = collect(get_declared_classes())
            ->filter(fn ($name) => str_starts_with($name, 'App\\Issues\\Checkers'))
            ->map(fn ($name) => new ReflectionClass($name))
            ->filter(fn ($reflect) => $reflect->implementsInterface(IssueCheck::class))
            ->map(fn ($reflect) => $reflect->getName())
            ->map(fn ($name) => app($name));
    }

    public function setOutput(OutputStyle $output): void
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
                if (! is_null($this->output)) {
                    $shortName = Str::replace('App\\Issues\\Checkers\\', '', get_class($checker));
                    $this->output->writeln("Getting issues for: $shortName");
                }
                $this->issues = $this->issues->union($checker->getIssues());
            }
        }

        return $this->issues;
    }

    /**
     * This is mostly a helper function. It can be run in tinker like this:
     * IssueChecker::getUnfixableIssueTypes()
     *
     * The idea here is that we should have possible fixes for most if not all of these issues. But checking every file
     * to see which ones we haven't yet updated can be tedious. Hence, this helper function.
     */
    public static function getUnfixableIssueTypes(): Collection
    {
        return collect(get_declared_classes())
            ->filter(fn ($name) => str_starts_with($name, 'App\\Issues\\Types'))
            ->map(fn ($name) => new ReflectionClass($name))
            ->filter(fn ($reflect) => $reflect->isSubclassOf(IssueBase::class))
            ->filter(fn ($reflect) => ! array_key_exists(ICanFixThem::class, $reflect->getTraits()))
            ->map(fn ($reflect) => $reflect->getName())
            ->values();
    }

    /**
     * @return Collection<IssueCheck>
     */
    public function getIssueCheckers(): Collection
    {
        return $this->checkers;
    }
}
