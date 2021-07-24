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
        $this->message->text($this->getHelperMessage($slackId));

        $this->message->text("If this is a mistake, please click this button to get connected to someone who can help.");

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

    /**
     * @param $slackId
     */
    public static function getHelperMessage($slackId, $doNothingMessage=true): string
    {
        /** @var Customer $customer */
        $customer = Customer::whereSlackId($slackId)->first();

        if($doNothingMessage) {
            $doNothingContent = " If you do nothing, your account will become a single channel guest in #public on August 15th.";
        } else {
            $doNothingContent = "";
        }

        if (is_null($customer)) {
            return "I don't have this slack account associated with a member account.$doNothingContent";
        } else if (!$customer->member) {
            return "I do have this slack account associated with your membership, but it doesn't appear that you're a member in good standing.$doNothingContent";
        } else {
            return "You seem to be a known customer and a member. I'm not sure how we got here.";
        }
    }
}
