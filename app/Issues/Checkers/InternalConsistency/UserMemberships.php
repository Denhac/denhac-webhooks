<?php

namespace App\Issues\Checkers\InternalConsistency;


use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\UserMembershipDoesNotExistInOurLocalDatabase;
use App\Issues\Types\InternalConsistency\UserMembershipNotFoundOnRemote;
use App\Issues\Types\InternalConsistency\UserMembershipStatusDiffers;
use App\UserMembership;

class UserMemberships implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    protected function generateIssues(): void
    {
        $userMembershipsApi = $this->issueData->wooCommerceUserMemberships();
        $userMembershipsModels = UserMembership::all();

        foreach ($userMembershipsApi as $userMembershipApi) {
            $um_id = $userMembershipApi['id'];
            $um_status = $userMembershipApi['status'];

            $model = $userMembershipsModels->where('id', $um_id)->first();

            if(is_null($model)) {
                $this->issues->add(new UserMembershipDoesNotExistInOurLocalDatabase($um_id));

                continue;
            }

            if ($model->status != $um_status) {
                $this->issues->add(new UserMembershipStatusDiffers($um_id, $um_status, $model->status));
            }
        }

        foreach ($userMembershipsModels as $userMembershipsModel) {
            /** @var UserMembership $userMembershipsModel */
            $um_id = $userMembershipsModel->id;

            $api = $userMembershipsApi->where('id', $um_id)->first();

            if (is_null($api)) {
                $this->issues->add(new UserMembershipNotFoundOnRemote($um_id));
            }
        }
    }
}
