<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class CustomerDataDiffers extends IssueBase
{
    private MemberData $member;

    private Collection $differentProperties;

    public function __construct(MemberData $member, Collection $differentProperties)
    {
        $this->member = $member;
        $this->differentProperties = $differentProperties;
    }

    public static function getIssueNumber(): int
    {
        return 215;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Customer data differs';
    }

    public function getIssueText(): string
    {
        $imploded = $this->differentProperties->implode(', ');

        return "{$this->member->first_name} {$this->member->last_name} ({$this->member->id}) differs from our database with these properties: $imploded";
    }
}
