<?php

namespace App\Issues\Types\Slack;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UserMissingSlackLink extends IssueBase
{
    use ICanFixThem;

    public function __construct(
        private $slackUser,
        private $membersForEmail,
    ) {}

    public static function getIssueNumber(): int
    {
        return 304;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Slack: User missing slack link';
    }

    public function getIssueText(): string
    {
        $members = $this->membersForEmail
            ->map(fn ($m) => "{$m->first_name} {$m->last_name} ($m->id)")
            ->implode(', ');

        return "{$this->slackUser['name']} with slack id ({$this->slackUser['id']}) probably belongs to one of these users: $members";
    }

    public function fix(): bool
    {
        $choices = $this->issueFixChoice();

        foreach ($this->membersForEmail as $m) {
            $choices->option("{$m->first_name} {$m->last_name} ($m->id)", fn () => $this->assignToMember($m));
        }

        return $choices->run();
    }

    private function assignToMember(MemberData $member): true
    {
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($member->id, [
                'meta_data' => [
                    [
                        'key' => 'access_slack_id',
                        'value' => $this->slackUser['id'],
                    ],
                ],
            ]);

        return true;
    }
}
