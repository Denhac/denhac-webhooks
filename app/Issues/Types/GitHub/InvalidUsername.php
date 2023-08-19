<?php

namespace App\Issues\Types\GitHub;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class InvalidUsername extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;
    private string $correctedUsername;

    public function __construct(MemberData $member, string $correctedUsername)
    {
        $this->member = $member;
        $this->correctedUsername = $correctedUsername;
    }

    public static function getIssueNumber(): int
    {
        return 402;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "GitHub: Invalid GitHub username";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the invalid format GitHub username \"{$this->member->githubUsername}\". It may be \"{$this->correctedUsername}\"";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option("Use suggested username: $this->correctedUsername", fn() => $this->useSuggestedUsername())
            ->option("Clear GitHub username field for member", fn() => $this->clearGitHubUsernameField())
            ->run();
    }

    private function useSuggestedUsername(): bool
    {
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($this->member->id, [
                'meta_data' => [
                    [
                        'key' => 'github_username',
                        'value' => $this->correctedUsername,
                    ],
                ],
            ]);
        return true;
    }

    private function clearGitHubUsernameField(): bool
    {
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($this->member->id, [
                'meta_data' => [
                    [
                        'key' => 'github_username',
                        'value' => null,
                    ],
                ],
            ]);
        return true;
    }
}
