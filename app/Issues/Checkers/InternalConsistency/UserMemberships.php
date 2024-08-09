<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\DataCache\WooCommerceUserMemberships;
use App\External\HasApiProgressBar;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\InternalConsistency\UserMembershipDoesNotExistInOurLocalDatabase;
use App\Issues\Types\InternalConsistency\UserMembershipNotFoundOnRemote;
use App\Issues\Types\InternalConsistency\UserMembershipStatusDiffers;
use App\Models\UserMembership;

class UserMemberships implements IssueCheck
{
    use IssueCheckTrait;
    use HasApiProgressBar;

    public function __construct(
        private readonly WooCommerceUserMemberships $wooCommerceUserMemberships
    )
    {
    }

    protected function generateIssues(): void
    {
        $userMembershipsApi = $this->wooCommerceUserMemberships->get();
        $userMembershipsModels = UserMembership::all();

        $apiProgress = $this->apiProgress('Checking User Memberships in API');
        $apiProgress->setProgress(0, $userMembershipsApi->count());
        foreach ($userMembershipsApi as $userMembershipApi) {
            $apiProgress->step();

            $um_id = $userMembershipApi['id'];
            $um_status = $userMembershipApi['status'];

            $model = $userMembershipsModels->where('id', $um_id)->first();

            if (is_null($model)) {
                $this->issues->add(new UserMembershipDoesNotExistInOurLocalDatabase($um_id));

                continue;
            }

            if ($model->status != $um_status) {
                $this->issues->add(new UserMembershipStatusDiffers($um_id, $um_status, $model->status));
            }
        }

        $dbProgress = $this->apiProgress('Checking User Memberships in Database');
        $dbProgress->setProgress(0, $userMembershipsApi->count());
        foreach ($userMembershipsModels as $userMembershipsModel) {
            $dbProgress->step();

            /** @var UserMembership $userMembershipsModel */
            $um_id = $userMembershipsModel->id;

            $api = $userMembershipsApi->where('id', $um_id)->first();

            if (is_null($api)) {
                $this->issues->add(new UserMembershipNotFoundOnRemote($um_id));
            }
        }
    }
}
