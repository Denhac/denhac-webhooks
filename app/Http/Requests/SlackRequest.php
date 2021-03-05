<?php

namespace App\Http\Requests;

use App\Customer;
use Illuminate\Http\Request;

class SlackRequest extends Request
{
    private ?array $payload_json = null;

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

    public function customer(): ?Customer
    {
        $userId = $this->get_user_id();

        if (is_null($userId)) {
            return null;
        }

        /** @var Customer $customer */
        return Customer::whereSlackId($userId)->first();
    }

    private function get_user_id()
    {
        $userID = $this->get('user_id');

        if (! is_null($userID)) {
            return $userID;
        }

        $payload = json_decode($this->get('payload'), true);

        if (is_null($payload)) {
            return null;
        }

        if (! array_key_exists('user', $payload)) {
            return null;
        }

        if (! array_key_exists('id', $payload['user'])) {
            return null;
        }

        return $payload['user']['id'];
    }
}
