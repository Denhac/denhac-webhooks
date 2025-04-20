<?php

namespace App\Issues;

use App\Issues\Checkers\IssueCheck;
use App\Issues\Fixing\Fixable;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use function Laravel\Prompts\info;

class IssueChecker
{
    protected Collection $checkers;

    protected ?Collection $issues = null;

    public function __construct()
    {
        $this->checkers = collect(get_declared_classes())
            ->filter(fn ($name) => str_starts_with($name, 'App\\Issues\\Checkers'))
            ->map(fn ($name) => new ReflectionClass($name))
            ->filter(fn ($reflect) => $reflect->implementsInterface(IssueCheck::class))
            ->map(fn ($reflect) => $reflect->getName())
            ->map(fn ($name) => app($name));
    }

    /**
     * @return Collection<IssueBase>
     */
    public function getIssues(): Collection
    {
        if (is_null($this->issues)) {
            $this->issues = collect();

            foreach ($this->getIssueCheckers() as $checker) {
                $shortName = Str::replace('App\\Issues\\Checkers\\', '', get_class($checker));
                info("Getting issues for: $shortName");
                $this->issues = $this->issues->merge($checker->getIssues());
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
            ->filter(fn ($reflect) => !$reflect->implementsInterface(Fixable::class))
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
