<?php

namespace App\Reactors;

use App\Actions\Slack\UpdateSpaceBotAppHome;
use App\StorableEvents\OctoPrintStatusUpdated;
use App\UserMembership;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class AppHomeReactor extends Reactor implements ShouldQueue
{
    public function onOctoPrintStatusUpdated(OctoPrintStatusUpdated $event)
    {
        $plan_id = UserMembership::MEMBERSHIP_3DP_USER;
        $this->updateHomeForMemberships($plan_id);
    }

    /**
     * @param int $plan_id
     */
    protected function updateHomeForMemberships(int $plan_id): void
    {
        /** @var Collection $slack_ids */
        $slack_ids = UserMembership::where('plan_id', $plan_id)
            ->with('customer')->get()
            ->map(function ($um) {
                return $um->customer->slack_id;
            })
            ->reject(null)
            ->unique();

        $this->updateHomeForSlackIDs($slack_ids);
    }

    /**
     * @param Collection $slack_ids
     */
    protected function updateHomeForSlackIDs(Collection $slack_ids): void
    {
        $slack_ids->each(function ($slack_id) {
            /** @var UpdateSpaceBotAppHome $updateSpaceBotAppHome */
            $updateSpaceBotAppHome = app(UpdateSpaceBotAppHome::class);
            $updateSpaceBotAppHome
                ->onQueue()
                ->execute($slack_id);
        });
    }
}
