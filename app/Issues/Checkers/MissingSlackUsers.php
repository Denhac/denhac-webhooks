<?php

namespace App\Issues\Checkers;


use App\Issues\IssueData;
use Illuminate\Support\Collection;

class MissingSlackUsers implements IssueCheck
{
    use SlackMembershipHelper;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with missing slack accounts";
    }

    public function getIssues(): Collection
    {
        $issues = collect();

        $members = $this->issueData->members();
        $slackUsers = $this->issueData->slackUsers();

        $members
            ->each(function ($member) use ($issues, $slackUsers) {
                if (!$member['is_member']) {
                    return;
                }

                $slackForMember = $slackUsers
                    ->filter(function ($user) use ($member) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($slackForMember->count() == 0) {
                    $message = "{$member['first_name']} {$member['last_name']} ({$member['id']}) doesn't appear to have a slack account";
                    $issues->add($message);
                }
            });

        return $issues;
    }
}
