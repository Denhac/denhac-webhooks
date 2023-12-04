<?php

namespace App\Projectors;

use App\Models\Waiver;
use App\StorableEvents\Waiver\WaiverAccepted;
use App\StorableEvents\Waiver\WaiverAssignedToCustomer;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

class WaiverProjector extends Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        Waiver::truncate();
    }

    public function onWaiverAccepted(WaiverAccepted $event)
    {
        $content = $event->waiverEvent['content'];

        $waiverId = $content['id'];
        $templateId = $content['template_id'];
        $templateVersion = $content['template_version'];
        $data = $content['data'];

        $firstName = null;
        $lastName = null;
        $email = null;

        foreach ($data as $field) {
            if (! array_key_exists('type', $field)) {
                continue;
            }

            $type = $field['type'];

            // The first field should be the person being waived. Otherwise the only field we can check is 'title' which
            // wasn't set in stone at the time of this writing.
            if ($type == 'name_field' && is_null($firstName)) {
                $firstName = $field['first_name'];
                $lastName = $field['last_name'];
            } elseif ($type == 'email_field') {
                $email = $field['value'];
            }
        }

        Waiver::create([
            'waiver_id' => $waiverId,
            'template_id' => $templateId,
            'template_version' => $templateVersion,
            'status' => 'accepted',
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    public function onWaiverAssignedToCustomer(WaiverAssignedToCustomer $event)
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('waiver_id', $event->waiverId)->first();
        $waiver->customer_id = (int) $event->customerId;
        $waiver->save();
    }
}
