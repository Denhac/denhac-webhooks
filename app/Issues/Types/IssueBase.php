<?php

namespace App\Issues\Types {
    abstract class IssueBase
    {
        public function getIssueURL(): string
        {
            return sprintf('https://github.com/Denhac/denhac-webhooks/wiki/Issues#%d', static::getIssueNumber());
        }

        abstract public static function getIssueNumber(): int;

        abstract public static function getIssueTitle(): string;

        abstract public function getIssueText(): string;
    }
}
