<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use Spatie\QueueableAction\QueueableAction;

class RemoveFromUserGroup
{
    use QueueableAction;
    use SlackActionTrait;
    use StaticAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($customerId, $userGroupHandle)
    {
        $slackId = $this->slackIdFromGeneralId($customerId);

        throw_if(is_null($slackId), "Customer $customerId cannot be removed from usergroup $userGroupHandle!");

        $usergroup = $this->slackApi->usergroups->byName($userGroupHandle);

        throw_if(is_null($usergroup), "Couldn't find usergroup for $userGroupHandle");

        $id = $usergroup['id'];
        $users = collect($usergroup['users'])
            ->filter(function ($user_id) use ($slackId) {
                return $user_id != $slackId;
            });

        $this->slackApi->usergroups->users->update($id, $users);
    }

    public function middleware()
    {
        return [
            SlackRateLimit::usergroups_list(),
            SlackRateLimit::usergroups_update(),
        ];
    }
}
