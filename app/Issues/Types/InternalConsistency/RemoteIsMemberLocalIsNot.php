<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class RemoteIsMemberLocalIsNot extends IssueBase
{
    public static function getIssueNumber(): int
    {
        return 206;
    }

    public static function getIssueTitle(): string
    {
        // TODO: Implement getIssueTitle() method.
    }

    public function getIssueText(): string
    {
        // TODO: Implement getIssueText() method.
    }
}
