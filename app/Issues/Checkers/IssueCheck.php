<?php

namespace App\Issues\Checkers;


use Illuminate\Support\Collection;

interface IssueCheck
{
    public function issueTitle(): string;

    public function getIssues(): Collection;
}
