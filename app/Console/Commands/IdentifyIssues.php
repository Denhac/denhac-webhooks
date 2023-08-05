<?php

namespace App\Console\Commands;

use App\Card;
use App\Google\GmailEmailHelper;
use App\Google\GoogleApi;
use App\Issues\IssueChecker;
use App\Slack\SlackApi;
use App\Subscription;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class IdentifyIssues extends Command
{
    const ISSUE_WITH_A_CARD = 'Issue with a card';
    const ISSUE_SLACK_ACCOUNT = 'Issue with a Slack account';
    const ISSUE_GOOGLE_GROUPS = 'Issue with google groups';
    const ISSUE_INTERNAL_CONSISTENCY = 'Issue with our store\'s internal consistency';

    const SYSTEM_WOOCOMMERCE = 'WooCommerce';
    const SYSTEM_PAYPAL = 'PayPal';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:identify-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies issues with membership and access';

    /**
     * @var MessageBag
     */
    private $issues;
    /**
     * @var WooCommerceApi
     */
    private $wooCommerceApi;
    /**
     * @var SlackApi
     */
    private $slackApi;
    /**
     * @var GoogleApi
     */
    private $googleApi;

    /**
     * Create a new command instance.
     *
     * @param WooCommerceApi $wooCommerceApi
     * @param SlackApi $slackApi
     * @param GoogleApi $googleApi
     */
    public function __construct(WooCommerceApi $wooCommerceApi,
                                SlackApi       $slackApi,
                                GoogleApi      $googleApi)
    {
        parent::__construct();

        $this->issues = new MessageBag();
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
        $this->googleApi = $googleApi;
    }

    /**
     * Execute the console command.
     *
     * @throws ApiCallFailed
     */
    public function handle(): void
    {
        $this->info('Identifying issues');

        /** @var IssueChecker $issueChecker */
        $issueChecker = app(IssueChecker::class);
        $issueChecker->setOutput($this->output);

        $issues = $issueChecker->getIssues();
        $this->info("There are {$issues->count()} total issues.");
        $this->info('');

        foreach ($issues->keys() as $issueKey) {
            $this->info($issueKey);
            foreach ($issues->get($issueKey) as $issue) {
                $this->info($issue);
            }
            $this->info('');
        }
//        $members = $this->getMembers();=
//        $this->extraSlackUsers($members);
//        $this->missingSlackUsers($members);
//        $this->googleGroupIssues($members);=
//
//        $this->printIssues();
    }

    private function printIssues()
    {
        $this->info("There are {$this->issues->count()} total issues.");
        $this->info('');
        collect($this->issues->keys())
            ->each(function ($key) {
                $knownIssues = collect($this->issues->get($key));
                $this->info("$key ({$knownIssues->count()})");

                $knownIssues
                    ->map(function ($issue) {
                        $this->info(">>> $issue");
                    });
                $this->info('');
            });
    }

    private function extraSlackUsers(Collection $members)
    {
        $slackUsers = $this->slackApi->users->list()
            ->filter(function ($user) {
                if (array_key_exists('is_bot', $user) && $user['is_bot']) {
                    return false;
                }

                if (
                    $user['id'] == 'UNEA0SKK3' || // slack-api
                    $user['id'] == 'USLACKBOT' // slackbot
                ) {
                    return false;
                }

                return true;
            });

        $slackUsers
            ->each(function ($user) use ($members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($membersForSlackId->count() == 0) {
                    if ($this->isFullSlackUser($user)) {
                        $message = "{$user['name']} with slack id ({$user['id']}) is a full user in slack but I have no membership record of them.";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                    }

                    return;
                }

                $member = $membersForSlackId->first();

                if ($member['is_member']) {
                    if (array_key_exists('is_invited_user', $user) && $user['is_invited_user']) {
                        return; // Do nothing, we've sent the invite and that's all we can do.
                    } else if (array_key_exists('deleted', $user) && $user['deleted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is deleted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                    } else if (array_key_exists('is_restricted', $user) && $user['is_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is restricted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                    } else if (array_key_exists('is_ultra_restricted', $user) && $user['is_ultra_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is ultra restricted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                    }
                } elseif ($this->isFullSlackUser($user)) {
                    $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is not an active member but they have a full slack account.";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                }
            });
    }

    private function isFullSlackUser($slackUser)
    {
        if (
            (array_key_exists('deleted', $slackUser) && $slackUser['deleted']) ||
            (array_key_exists('is_restricted', $slackUser) && $slackUser['is_restricted']) ||
            (array_key_exists('is_ultra_restricted', $slackUser) && $slackUser['is_ultra_restricted'])
        ) {
            return false;
        }

        return true;
    }

    private function missingSlackUsers(Collection $members)
    {
        $slackUsers = $this->slackApi->users->list();

        $members
            ->each(function ($member) use ($slackUsers) {
                if (!$member['is_member']) {
                    return;
                }

                $slackForMember = $slackUsers
                    ->filter(function ($user) use ($member) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($slackForMember->count() == 0) {
                    $message = "{$member['first_name']} {$member['last_name']} ({$member['id']}) doesn't appear to have a slack account";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                }
            });
    }

    private function googleGroupIssues(Collection $members)
    {
        $groups = $this->googleApi->groupsForDomain('denhac.org')
            ->filter(function ($group) {
                // TODO handle excluded groups in a better way
                return $group != 'denhac@denhac.org' &&
                    $group != 'lpfmerrors@denhac.org';
            });

        $emailsToGroups = collect();

        $groups->each(function ($group) use (&$emailsToGroups) {
            $membersInGroup = $this->googleApi->group($group)->list();

            $membersInGroup->each(function ($groupMember) use ($group, &$emailsToGroups) {
                $groupMember = GmailEmailHelper::handleGmail(Str::lower($groupMember));
                $groupsForEmail = $emailsToGroups->get($groupMember, collect());
                $groupsForEmail->add($group);
                $emailsToGroups->put($groupMember, $groupsForEmail);
            });
        });

        $emailsToGroups->each(function ($groupsForEmail, $email) use ($groups, $members) {
            /** @var Collection $groupsForEmail */

            // Ignore groups of ours that are part of another group
            if ($groups->contains($email)) {
                return;
            }

            $membersForEmail = $members
                ->filter(function ($member) use ($email) {
                    /** @var Collection $memberEmails */
                    $memberEmails = $member['email'];

                    return $memberEmails->contains(Str::lower($email));
                });

            if ($membersForEmail->count() > 1) {
                $message = "More than 2 members exist for email address $email";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);

                return;
            }

            if ($membersForEmail->count() == 0) {
                $message = "No member found for email address $email in groups: {$groupsForEmail->implode(', ')}";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);

                return;
            }

            $member = $membersForEmail->first();

            if (!$member['is_member']) {
                $message = "{$member['first_name']} {$member['last_name']} with email ($email) is not an active member but is in groups: {$groupsForEmail->implode(', ')}";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);
            }
        });

        $members->each(function ($member) use ($emailsToGroups) {
            /** @var Collection $memberEmails */
            $memberEmails = $member['email'];

            if ($memberEmails->isEmpty()) {
                return;
            }

            if (!$member['is_member']) {
                return;
            }

            $membersGroupMailing = 'members@denhac.org'; // TODO dedupe this
            $memberGroupEmails = [
                'members@denhac.org',
                'announce@denhac.org',
            ];

            // TODO At least one email is on some list


            $memberHasEmailInMembersList = $memberEmails
                ->filter(function ($memberEmail) use ($emailsToGroups, $memberGroupEmails) {
                    if (!$emailsToGroups->has($memberEmail)) {
                        return false;
                    }
                    foreach ($memberGroupEmails as $groupEmail) {
                        if ($emailsToGroups->get($memberEmail)->contains($groupEmail)) {
                            return false;
                        }
                    }
                    return True;
                })
                ->isNotEmpty();

            if ($memberHasEmailInMembersList) {
                return;
            }

            if ($member['is_member']) {
                $message = "{$member['first_name']} {$member['last_name']} with email ({$memberEmails->implode(', ')}) is an active member but is not part of $membersGroupMailing";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);
            }
        });
    }
}
