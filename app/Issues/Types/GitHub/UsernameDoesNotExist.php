<?php

namespace App\Issues\Types\GitHub;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UsernameDoesNotExist extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 403;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'GitHub: Username does not exist';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the GitHub username \"{$this->member->githubUsername}\" which does not exist";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Clear GitHub username field for member', function () {
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
            })
            ->run();
    }
}
