<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\DataCache\WooCommerceSubscriptions;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\InternalConsistency\SubscriptionDoesNotExistInOurLocalDatabase;
use App\Issues\Types\InternalConsistency\SubscriptionNotFoundOnRemote;
use App\Issues\Types\InternalConsistency\SubscriptionStatusDiffers;
use App\Models\Subscription;

class SubscriptionIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly WooCommerceSubscriptions $wooCommerceSubscriptions
    ) {}

    public function generateIssues(): void
    {
        $subscriptions_api = $this->wooCommerceSubscriptions->get();
        $subscriptions_models = Subscription::all();

        foreach ($subscriptions_api as $subscription_api) {
            $sub_id = $subscription_api['id'];
            $sub_status = $subscription_api['status'];

            $model = $subscriptions_models->find($sub_id);

            if (is_null($model)) {
                $this->issues->add(new SubscriptionDoesNotExistInOurLocalDatabase($sub_id));

                continue;
            }

            if ($model->status != $sub_status) {
                $this->issues->add(new SubscriptionStatusDiffers($sub_id, $sub_status, $model->status));
            }
        }

        foreach ($subscriptions_models as $subscription_model) {
            /** @var Subscription $subscription_model */
            $sub_id = $subscription_model->id;

            $api = $subscriptions_api->where('id', $sub_id)->first();

            if (is_null($api)) {
                $this->issues->add(new SubscriptionNotFoundOnRemote($sub_id));
            }
        }
    }
}
