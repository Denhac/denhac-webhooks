<?php

namespace App\Projectors;

use App\StorableEvents\WaiverAccepted;
use App\Waiver;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class WaiverProjector extends Projector
{
    public function onWaiverAccepted(WaiverAccepted $event)
    {
        $content = $event['waiverEvent']['content'];

        $waiverId = $content['id'];
        $templateId = $content['template_id'];
        $templateVersion = $content['template_version'];
        $data = $content['data'];

        $firstName = null;
        $lastName = null;
        $email = null;

        foreach($data as $field) {
            if(! array_key_exists('type', $field)) {
                continue;
            }

            $type = $field['type'];

            // The first field should be the person being waived. Otherwise the only field we can check is 'title' which
            // wasn't set in stone at the time of this writing.
            if($type == 'name_field' && is_null($firstName)) {
                $firstName = $field['first_name'];
                $lastName = $field['last_name'];
            } else if($type == 'email_field') {
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
}
