<?php

namespace App\Http\Requests;

use App\Customer;
use Illuminate\Http\Request;

class SlackRequest extends Request
{
    private ?array $payload_json = null;
    private ?array $event_json = null;

    public function payload()
    {
        if (is_null($this->payload_json)) {
            $this->payload_json = json_decode($this->get('payload'), true);

            if (is_null($this->payload_json)) {
                throw new \Exception('Slack request has no payload');
            }
        }

        return $this->payload_json;
    }

    private function event()
    {
        if (is_null($this->event_json)) {
            $this->event_json = json_decode($this->get('event'), true);

            if (is_null($this->event_json)) {
                throw new \Exception('Slack request has no event');
            }
        }

        return $this->event_json;
    }

    public function customer(): ?Customer
    {
        $userId = $this->getSlackId();

        if (is_null($userId)) {
            return null;
        }

        return Customer::whereSlackId($userId)->first();
    }

    public function getSlackId()
    {
        $userID = $this->get('user_id');

        if (! is_null($userID)) {
            return $userID;
        }

        $data = json_decode($this->get('payload'), true);

        if (is_null($data)) {
            $data = json_decode($this->get('event'), true);

            if (is_null($data)) {
                return null;
            }
        }

        if (! array_key_exists('user', $data)) {
            return null;
        }

        if(is_string($data['user'])) {
            return $data['user'];
        }

        if (! array_key_exists('id', $data['user'])) {
            return null;
        }

        return $data['user']['id'];
    }
}
