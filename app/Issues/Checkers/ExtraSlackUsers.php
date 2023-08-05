<?php

namespace App\Issues\Checkers;


use App\Issues\IssueData;
use Illuminate\Support\Collection;

class ExtraSlackUsers implements IssueCheck
{
    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with a Slack account";
    }

    public function getIssues(): Collection
    {
        $issues = collect();

        $slackUsers = $this->issueData->slackUsers()
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

        $members = $this->issueData->members();

        $slackUsers
            ->each(function ($user) use ($issues, $members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($membersForSlackId->count() == 0) {
                    if ($this->isFullSlackUser($user)) {
                        $message = "{$user['name']} with slack id ({$user['id']}) is a full user in slack but I have no membership record of them.";
                        $issues->add($message);
                    }

                    return;
                }

                $member = $membersForSlackId->first();

                if ($member['is_member']) {
                    if (array_key_exists('is_invited_user', $user) && $user['is_invited_user']) {
                        return; // Do nothing, we've sent the invite and that's all we can do.
                    } else if (array_key_exists('deleted', $user) && $user['deleted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is deleted, but they are a member";
                        $issues->add($message);

                    } else if (array_key_exists('is_restricted', $user) && $user['is_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is restricted, but they are a member";
                        $issues->add($message);

                    } else if (array_key_exists('is_ultra_restricted', $user) && $user['is_ultra_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is ultra restricted, but they are a member";
                        $issues->add($message);

                    }
                } elseif ($this->isFullSlackUser($user)) {
                    $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is not an active member but they have a full slack account.";
                    $issues->add($message);
                }
            });

        return $issues;
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
}
