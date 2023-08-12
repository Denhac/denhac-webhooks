<?php

namespace App\Issues\Types\Slack;

use App\Issues\Types\IssueBase;

class FullUserNoRecord extends IssueBase
{
    private $slackUser;

    public function __construct($slackUser)
    {
        $this->slackUser = $slackUser;
    }

    public static function getIssueNumber(): int
    {
        return 301;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Slack: Full user no record";
    }

    public function getIssueText(): string
    {
        return "{$this->slackUser['name']} with slack id ({$this->slackUser['id']}) is a full user in slack but I have no membership record of them.";
    }
}
