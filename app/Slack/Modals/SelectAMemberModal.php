<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackOptions;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class SelectAMemberModal implements ModalInterface
{
    use ModalTrait;

    private const SELECT_A_MEMBER_BLOCK_ID = 'select-a-member-block';
    private const SELECT_A_MEMBER_ACTION_ID = 'select-a-member-action';

    /**
     * @var Modal
     */
    private $modalView;

    public function __construct($callbackOrModalClass)
    {
        $callbackId = $callbackOrModalClass;
        if (strpos($callbackId, 'App\\Slack\\Modals') === 0) {
            $callbackId = $callbackOrModalClass::callbackId();
        }

        $this->modalView = Slack::newModal()
            ->callbackId(self::callbackId())
            ->title('Select A Member')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit')
            ->privateMetadata($callbackId);

        $this->modalView->newInput()
            ->label('Member')
            ->blockId(self::SELECT_A_MEMBER_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::SELECT_A_MEMBER_ACTION_ID)
            ->placeholder('Select a Member')
            ->minQueryLength(2);
    }

    public static function callbackId()
    {
        return 'select-a-member-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::SELECT_A_MEMBER_BLOCK_ID][self::SELECT_A_MEMBER_ACTION_ID]['selected_option']['value'];

        $matches = [];
        $result = preg_match('/customer\-(\d+)/', $selectedOption, $matches);

        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $selectedOption");
        }

        $customer_id = $matches[1];
        $nextCallbackId = $request->payload()['view']['private_metadata'];

        $modalClass = ModalTrait::getModal($nextCallbackId);
        /** @var ModalTrait $modal */
        $modal = new $modalClass($customer_id);

        return $modal->push();
    }

    public static function getOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        $customers = Customer::with('subscriptions')->get();

        foreach ($customers as $customer) {
            /** @var Customer $customer */
            $name = "{$customer->first_name} {$customer->last_name}";

            $hasAnySubscriptions = $customer->subscriptions->count() > 0;
            $hasNeedIdCheck = $customer->subscriptions->where('status', 'need-id-check')->count() > 0;

            if (! $hasAnySubscriptions) {
                continue;
            } elseif ($hasNeedIdCheck) {
                $text = "$name (Need ID Check)";
            } elseif ($customer->member) {
                $text = "$name (Member)";
            } else {
                $text = "$name (Not a Member)";
            }

            $value = "customer-{$customer->woo_id}";

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
