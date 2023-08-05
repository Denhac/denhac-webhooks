<?php

namespace App\Issues\Checkers;


use App\Issues\IssueData;
use App\Subscription;
use Illuminate\Support\Collection;

class InternalConsistencySubscriptionIssues implements IssueCheck
{
    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with our webhook server's internal consistency for subscriptions";
    }

    public function getIssues(): Collection
    {
        $issues = collect();
        $subscriptions_api = $this->issueData->wooCommerceSubscriptions();
        $subscriptions_models = Subscription::all();

        $subscriptions_api->each(function ($subscription_api) use ($issues, $subscriptions_models) {
            $sub_id = $subscription_api['id'];
            $sub_status = $subscription_api['status'];

            $model = $subscriptions_models->where('woo_id', $sub_id)->first();

            if (is_null($model)) {
                $message = "Subscription $sub_id doesn't exist in our local database";
                $issues->add($message);

                return;
            }

            if ($model->status != $sub_status) {
                $message = "Subscription $sub_id has api status $sub_status but local status {$model->status}";
                $issues->add($message);
            }
        });

        $subscriptions_models->each(function ($subscription_model) use ($issues, $subscriptions_api) {
            /** @var Subscription $subscription_model */
            $sub_id = $subscription_model->woo_id;

            $api = $subscriptions_api->where('id', $sub_id)->first();

            if (is_null($api)) {
                $message = "Subscription $sub_id exists in our local database but not on the website. Deleted?";
                $issues->add($message);
            }
        });

        return $issues;
    }
}
