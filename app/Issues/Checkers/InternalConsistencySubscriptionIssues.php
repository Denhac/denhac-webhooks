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
        return "Issue with our store\'s internal consistency for subscriptions";
    }

    public function getIssues(): Collection
    {
        $issues = collect();
        $subscriptions_api = $this->issueData->wooCommerceSubscriptions();

        $subscriptions_api->each(function ($issues, $subscription_api) {
            $sub_id = $subscription_api['id'];
            $sub_status = $subscription_api['status'];

            $model = Subscription::whereWooId($sub_id)->first();

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

        return $issues;
    }
}
