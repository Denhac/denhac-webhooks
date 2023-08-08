<?php

namespace App\Issues\Types;


abstract class IssueBase
{
    private int $issueNumber;

    public const ISSUE_CARD_WITH_NO_MATCHING_MEMBERSHIP = 1;
    public const ISSUE_CARD_CONNECTED_MULTIPLE_ACCOUNTS = 2;
    public const ISSUE_CARD_DIFFERENT_NAME = 3;
    public const ISSUE_CARD_ACTIVE_BUT_NOT_MEMBER = 4;
    public const ISSUE_CARD_NOT_ACTIVE_BUT_MEMBER = 5;
    public const ISSUE_GOOGLE_GROUP_TWO_MEMBERS_HAVE_SAME_EMAIL = 104;
    public const ISSUE_GOOGLE_GROUP_NO_MEMBER_FOUND_FOR_EMAIL = 105;
    public const ISSUE_GOOGLE_GROUP_NOT_ACTIVE_MEMBER_IN_GROUPS = 106;
    public const ISSUE_GOOGLE_GROUP_ACTIVE_MEMBER_NOT_IN_GROUPS = 107;

    protected function __construct($issueCode)
    {
        $this->issueNumber = $issueCode;
    }

    public function getIssueURL(): string
    {
        return sprintf("https://github.com/Denhac/denhac-webhooks/wiki/Issues#%04d", $this->issueNumber);
    }

    public function getIssueNumber(): int
    {
        return $this->issueNumber;
    }

    public static abstract function getIssueTitle(): string;

    public abstract function getIssueText(): string;
}
