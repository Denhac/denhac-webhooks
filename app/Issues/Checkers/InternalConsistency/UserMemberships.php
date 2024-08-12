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
        $userMembershipsApi = $this->wooCommerceUserMemberships->get()->groupBy(fn($um) => $um['id']);
        $userMembershipsModels = UserMembership::all()->groupBy(fn($um) => $um->id);

        $apiProgress = $this->apiProgress('Checking User Memberships in API');
        $apiProgress->setProgress(0, $userMembershipsApi->count());
        foreach ($userMembershipsApi as $um_id => $userMembershipApiCollection) {
            $apiProgress->step();

            $userMembershipApi = $userMembershipApiCollection->first();
            $um_status = $userMembershipApi['status'];

            $userMembershipModelsCollections = $userMembershipsModels->get($um_id);

            if (is_null($userMembershipModelsCollections)) {
                $this->issues->add(new UserMembershipDoesNotExistInOurLocalDatabase($um_id));

                continue;
            }

            $model = $userMembershipsModels->get($um_id)->first();

            if ($model->status != $um_status) {
                $this->issues->add(new UserMembershipStatusDiffers($um_id, $um_status, $model->status));
            }
        }

        $dbProgress = $this->apiProgress('Checking User Memberships in Database');
        $dbProgress->setProgress(0, $userMembershipsApi->count());
        foreach ($userMembershipsModels as $um_id => $_) {
            $dbProgress->step();

            $api = $userMembershipsApi->get($um_id);

            if (is_null($api)) {
                $this->issues->add(new UserMembershipNotFoundOnRemote($um_id));
            }
        }
    }
}
