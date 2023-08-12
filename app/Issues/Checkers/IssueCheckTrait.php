<?php

namespace App\Issues\Checkers;


use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

trait IssueCheckTrait
{
    /** @var Collection<IssueBase>|null  */
    private Collection|null $issues = null;

    public function getIssues(): Collection
    {
        if (is_null($this->issues)) {
            $this->issues = collect();

            $this->generateIssues();
        }

        return $this->issues;
    }

    protected abstract function generateIssues(): void;
}
