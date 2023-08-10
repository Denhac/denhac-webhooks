<?php

namespace App\Issues\Checkers;


use Illuminate\Support\Collection;

trait IssueCheckTrait
{
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
