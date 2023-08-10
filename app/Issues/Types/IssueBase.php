<?php

namespace App\Issues\Types {
    abstract class IssueBase
    {
        public const ISSUE_CARD_WITH_NO_MATCHING_MEMBERSHIP = 1;
        public const ISSUE_CARD_CONNECTED_MULTIPLE_ACCOUNTS = 2;
        public const ISSUE_CARD_DIFFERENT_NAME = 3;
        public const ISSUE_CARD_ACTIVE_BUT_NOT_MEMBER = 4;
        public const ISSUE_CARD_NOT_ACTIVE_BUT_MEMBER = 5;
        public const ISSUE_GOOGLE_GROUP_TWO_MEMBERS_HAVE_SAME_EMAIL = 104;
        public const ISSUE_GOOGLE_GROUP_NO_MEMBER_FOUND_FOR_EMAIL = 105;
        public const ISSUE_GOOGLE_GROUP_NOT_ACTIVE_MEMBER_IN_GROUPS = 106;
        public const ISSUE_GOOGLE_GROUP_ACTIVE_MEMBER_NOT_IN_GROUPS = 107;
        public const ISSUE_INTERNAL_CONSISTENCY = 201;

        public function getIssueURL(): string
        {
            return sprintf("https://github.com/Denhac/denhac-webhooks/wiki/Issues#%04d", static::getIssueNumber());
        }

        public static abstract function getIssueNumber(): int;

        public static abstract function getIssueTitle(): string;

        public abstract function getIssueText(): string;
    }
}
