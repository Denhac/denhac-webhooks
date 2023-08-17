<?php

namespace App\Issues\Types\GitHub;

use App\External\GitHub\GitHubApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UnknownGitHubUsernameInTeam extends IssueBase
{
    use ICanFixThem;

    private string $gitHubUsername;

    public function __construct(string $gitHubUsername)
    {
        $this->gitHubUsername = $gitHubUsername;
    }

    public static function getIssueNumber(): int
    {
        return 404;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "GitHub: Unknown git hub username in team";
    }

    public function getIssueText(): string
    {
        return "$this->gitHubUsername is in the members team but I have no record of them";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option("Remove from GitHub team", fn() => $this->removeFromGitHubTeam())
            ->option("Assign to member", fn() => $this->assignGitHubUsernameToMember())
            ->run();
    }

    private function removeFromGitHubTeam(): bool
    {
        /** @var GitHubApi $gitHubApi */
        $gitHubApi = app(GitHubApi::class);
        $gitHubApi->team("members")->remove($this->gitHubUsername);

        return true;
    }



    private function assignGitHubUsernameToMember(): bool
    {
        /** @var MemberData $member */
        $member = $this->selectMember();

        if (is_null($member)) {
            return false;
        }

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($member->id, [
                'meta_data' => [
                    [
                        'key' => 'github_username',
                        'value' => $this->gitHubUsername,
                    ],
                ],
            ]);

        return true;
    }
}
