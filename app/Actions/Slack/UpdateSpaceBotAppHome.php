<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\CommonResponses;
use App\External\Slack\SlackApi;
use App\Models\Customer;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\AppHome;
use Spatie\QueueableAction\QueueableAction;

class UpdateSpaceBotAppHome
{
    use QueueableAction;
    use StaticAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    private AppHome $home;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
        $this->home = Kit::newAppHome();
    }

    /**
     * Execute the action.
     */
    public function execute(string $slack_id): void
    {
        /** @var Customer $member */
        $member = Customer::whereSlackId($slack_id)->first();

        if (is_null($member)) {
            $this->home->text(CommonResponses::unrecognizedUser());
        } elseif (! $member->member) {
            $this->home->text(CommonResponses::notAMemberInGoodStanding());
        } else {
            $this->activeMember($member);
        }

        $this->slackApi->views->publish($slack_id, $this->home);
    }

    private function activeMember(Customer $member)
    {
        $this->home->text("You're an active member! Thank you for being part of denhac!");
    }
}
