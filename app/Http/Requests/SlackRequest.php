<?php

namespace App\Http\Requests;

use App\Customer;
use App\Slack\ValidatesSlackSignature;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlackRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesSlackSignature;

    /**
     * @var null
     */
    private $payload_json;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->payload_json = null;
    }

    /**
     * @throws ValidationException
     */
    public function validateResolved()
    {
        $secret = config('denhac.slack.spacebot_api_signing_secret');
        if(! $this->isSlackSignatureValid($this, $secret)) {
            throw new ValidationException(null, response()->json([
                'response_type' => 'ephemeral',
                'text' => "The signature from slack didn't match the computed value. Did our signing secret change?",
            ]));
        }
    }

    public function payload()
    {
        if(is_null($this->payload_json)) {
            $this->payload_json = json_decode($this->get("payload"), true);

            if (is_null($this->payload_json)) {
                throw new \Exception("Slack request has no payload");
            }
        }

        return $this->payload_json;
    }

    public function customer()
    {
        $userId = $this->get_user_id();

        if(is_null($userId)) {
            return null;
        }

        /** @var Customer $customer */
        $customer = Customer::whereSlackId($userId)->first();

        return $customer;
    }

    private function get_user_id()
    {
        $userID = $this->get('user_id');

        if(! is_null($userID)) {
            return $userID;
        }

        $payload = json_decode($this->get('payload'), true);

        if(is_null($payload)) {
            return null;
        }

        if(! array_key_exists("user", $payload)) {
            return null;
        }

        if(! array_key_exists("id", $payload["user"])) {
            return null;
        }

        $userID = $payload["user"]["id"];

        return $userID;
    }
}
