<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackOptions;
use App\Subscription;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class NeedIdCheckModal implements ModalInterface
{
    use ModalTrait;

    private const NEW_MEMBER_BLOCK_ID = 'new-member-block';
    private const NEW_MEMBER_ACTION_ID = 'new-member-action';

    /**
     * @var Modal
     */
    private $modalView;

    public function __construct()
    {
        $this->modalView = Slack::newModal()
            ->callbackId(self::callbackId())
            ->title('New Member Signup')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->label('New Member')
            ->blockId(self::NEW_MEMBER_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::NEW_MEMBER_ACTION_ID)
            ->placeholder('Select a Customer')
            ->minQueryLength(0);
    }

    public static function callbackId()
    {
        return 'membership-need-id-check-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::NEW_MEMBER_BLOCK_ID][self::NEW_MEMBER_ACTION_ID]['selected_option']['value'];

        $matches = [];
        $result = preg_match('/subscription\-(\d+)/', $selectedOption, $matches);

        if (! $result) {
            throw new \Exception("Option wasn't valid for subscription: $selectedOption");
        }

        $subscription_id = $matches[1];
        $modal = new NewMemberIdCheckModal($subscription_id);

        return $modal->push();
    }

    public static function getOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        $needIdCheckSubscriptions = Subscription::whereStatus('need-id-check')->with('customer')->get();

        foreach ($needIdCheckSubscriptions as $subscription) {
            /** @var Subscription $subscription */
            /** @var Customer $customer */
            $customer = $subscription->customer;
            $subscription_id = $subscription->getKey();

            if (is_null($customer)) {
                $name = 'Unknown Customer';
            } else {
                $name = "{$customer->first_name} {$customer->last_name}";
            }

            $text = "$name (Subscription #$subscription_id)";
            $value = "subscription-$subscription_id";

            $options->option($text, $value);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
