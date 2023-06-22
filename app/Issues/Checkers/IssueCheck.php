<?php

namespace App\Issues\Checkers;


use Illuminate\Support\Collection;

interface IssueCheck
{
    public function issueTitle();

    public function getIssues(): Collection;
}
