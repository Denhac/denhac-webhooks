<?php

namespace App\External\Slack\Modals;

use App\Customer;
use App\External\Slack\SlackOptions;
use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NeedIdCheckModal implements ModalInterface
{
    use ModalTrait;

    private const NEW_MEMBER = 'new-member';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Member Signup')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->label('New Member')
            ->blockId(self::NEW_MEMBER)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::NEW_MEMBER)
            ->placeholder('Select a Customer')
            ->minQueryLength(0);
    }

    public static function callbackId(): string
    {
        return 'membership-need-id-check-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::NEW_MEMBER][self::NEW_MEMBER]['selected_option']['value'];

        $matches = [];
        $result = preg_match('/customer-(\d+)/', $selectedOption, $matches);

        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $selectedOption");
        }

        $customer_id = $matches[1];
        $modal = new NewMemberIdCheckModal($customer_id);

        return $modal->update();
    }

    public static function getOptions(SlackRequest $request): SlackOptions
    {
        $options = SlackOptions::new();

        $customersNeedingIdCheck = Customer::with('memberships')
            ->where('id_checked', false)
            ->whereRelation('memberships', 'status', 'paused') // TODO Verify User Membership is 6410
            ->orderBy('woo_id', 'desc')
            ->get();

        foreach ($customersNeedingIdCheck as $customer) {
            /** @var Customer $customer */
            $name = "{$customer->first_name} {$customer->last_name}";

            $value = "customer-$customer->woo_id";

            $options->option($name, $value);
        }

        return $options;
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
