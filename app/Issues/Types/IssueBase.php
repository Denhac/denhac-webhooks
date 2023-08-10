<?php

namespace App\Issues\Types {
    abstract class IssueBase
    {
        public function getIssueURL(): string
        {
            return sprintf("https://github.com/Denhac/denhac-webhooks/wiki/Issues#%d", static::getIssueNumber());
        }

        public static abstract function getIssueNumber(): int;

        public static abstract function getIssueTitle(): string;

        public abstract function getIssueText(): string;
    }
}
