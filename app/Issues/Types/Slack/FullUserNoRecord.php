<?php

namespace App\Issues\Types\Slack;

use App\External\Slack\Channels;
use App\External\Slack\SlackApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;

class FullUserNoRecord extends IssueBase
{
    use ICanFixThem;

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

    public function fix(): bool
    {
        $this->info("Please note that assigning this account to a member will almost definitely");
        $this->info("leave their old account in this same state the next time someone checks for");
        $this->info("issues to fix.");
        $this->newLine();

        return $this->issueFixChoice()
            ->option("Deactivate Slack Account", fn() => $this->deactivateSlackAccount())
            ->option("Assign to member", fn() => $this->assignSlackUserToMember())
            ->run();
    }

    private function deactivateSlackAccount(): bool
    {
        /** @var SlackApi $slackApi */
        $slackApi = app(SlackApi::class);
        $channel = Channels::PUBLIC;
        $channel = $slackApi->conversations->toSlackIds($channel)->first();
        $slackApi->users->admin->setUltraRestricted($this->slackUser['id'], $channel);

        return true;
    }

    private function assignSlackUserToMember(): bool
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
                        'key' => 'access_slack_id',
                        'value' => $this->slackUser['id'],
                    ],
                ],
            ]);

        return true;
    }
}
