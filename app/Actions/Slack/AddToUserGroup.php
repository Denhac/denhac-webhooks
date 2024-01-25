<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use Spatie\QueueableAction\QueueableAction;

class AddToUserGroup
{
    use QueueableAction;
    use StaticAction;
    use SlackActionTrait;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($customerId, $userGroupHandle)
    {
        $slackId = $this->slackIdFromGeneralId($customerId);

        throw_if(is_null($slackId), "Customer $customerId cannot be added to usergroup $userGroupHandle!");

        $userGroup = $this->slackApi->usergroups->byName($userGroupHandle);

        throw_if(is_null($userGroup), "Couldn't find usergroup for $userGroupHandle");

        $id = $userGroup['id'];
        $users = collect($userGroup['users']);
        $users->add($slackId);

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
