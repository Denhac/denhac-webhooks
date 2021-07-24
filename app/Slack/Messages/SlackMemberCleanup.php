<?php

namespace App\Slack\Messages;


use App\Actions\Slack\SendMessage;
use App\Customer;
use App\Slack\BlockActions\HelpMemberCleanupButton;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;

class SlackMemberCleanup
{
    private Message $message;

    public function __construct($slackId)
    {
        $this->message = Kit::newMessage();
        $this->message->text("Hello! We're cleaning up our slack and making it denhac member's only.");

        /** @var Customer $customer */
        $customer = Customer::whereSlackId($slackId)->first();

        if(is_null($customer)) {
            $this->message->text("I don't have this slack account associated with a member account. If this is a mistake, please click this button to get connected to someone who can help.");
        } else if(! $customer->member) {
            $this->message->text("I do have your slack account associated with your membership, but it doesn't appear that you're a member in good standing. If this is a mistake, please click this button to get connected to someone who can help.");
        } else {
            throw new \Exception("I know this customer and they are a member: $slackId");
        }

        $this->message
            ->newActions(HelpMemberCleanupButton::blockId())
            ->newButton(HelpMemberCleanupButton::actionId())
            ->text("Help")
            ->asPrimary();
    }

    /**
     * @throws \Exception
     */
    public static function send($slackId) {
        $instance = new static($slackId);

        app(SendMessage::class)
            ->onQueue()
            ->execute($slackId, $instance->message);
    }
}
