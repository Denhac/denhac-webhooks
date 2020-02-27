<?php

namespace App\Projectors;

use App\PaypalBasedMember;
use App\StorableEvents\PaypalMemberCardUpdated;
use App\StorableEvents\PaypalMemberDeactivated;
use App\StorableEvents\PaypalMemberEmailUpdated;
use App\StorableEvents\PaypalMemberImported;
use App\StorableEvents\PaypalMemberNameUpdated;
use App\StorableEvents\PaypalMemberSlackIdUpdated;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class PaypalMemberProjector implements Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        PaypalBasedMember::truncate();
    }

    public function onPaypalMemberImported(PaypalMemberImported $event)
    {
        PaypalBasedMember::create([
            "paypal_id" => $event->paypal_id,
        ]);
    }

    public function onPaypalMemberNameUpdated(PaypalMemberNameUpdated $event)
    {
        $member = PaypalBasedMember::wherePaypalId($event->paypal_id)->first();
        $member->first_name = $event->first_name;
        $member->last_name = $event->last_name;
        $member->save();
    }

    public function onPaypalMemberCardUpdated(PaypalMemberCardUpdated $event)
    {
        $member = PaypalBasedMember::wherePaypalId($event->paypal_id)->first();
        $member->card = $event->card;
        $member->save();
    }

    public function onPaypalMemberSlackIdUpdated(PaypalMemberSlackIdUpdated $event)
    {
        $member = PaypalBasedMember::wherePaypalId($event->paypal_id)->first();
        $member->slack_id = $event->slack_id;
        $member->save();
    }

    public function onPaypalMemberEmailUpdated(PaypalMemberEmailUpdated $event)
    {
        $member = PaypalBasedMember::wherePaypalId($event->paypal_id)->first();
        $member->email = $event->email;
        $member->save();
    }

    /**
     * It's safe to do this because we won't let someone sign up via paypal again
     *
     * @param PaypalMemberDeactivated $event
     */
    public function onPaypalMemberDeactivated(PaypalMemberDeactivated $event)
    {
        PaypalBasedMember::wherePaypalId($event->paypal_id)->delete();
    }
}
