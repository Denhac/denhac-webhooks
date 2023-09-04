<?php

namespace App\External\OctoPrint;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

class OctoPrintApi
{
    private $host;

    /**
     * @var string|null
     */
    private $api_key;

    /**
     * @var Client
     */
    private $client;

    public function __construct($host, $ip = null, $api_key = null)
    {
        if (is_null($api_key)) {
            $api_key = setting("hosts.$host.api_key");
        }
        if (is_null($ip)) {
            $ip = setting("hosts.$host.ip");
        }

        $this->host = $host;
        $this->api_key = $api_key;

        $this->client = new Client([
            'base_uri' => "http://$ip/",
            RequestOptions::HEADERS => [
                'X-Api-Key' => $this->api_key,
            ],
        ]);
    }

    public function get_users()
    {
        $response = $this->client->get('/api/access/users');

        return collect(json_decode($response->getBody(), true));
    }

    public function get_user($username)
    {
        try {
            $response = $this->client->get("/api/access/users/$username");

            return json_decode($response->getBody(), true);
        } catch (ClientException $ex) {
            if ($ex->getCode() == 404) {
                return null;
            }
            throw $ex;
        }
    }

    public function add_user($username, $password, $active = true, $admin = false)
    {
        $response = $this->client->post('/api/access/users', [
            RequestOptions::JSON => [
                'name' => $username,
                'password' => $password,
                'active' => $active,
                'admin' => $admin,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function update_user($username, $active = true, $admin = false)
    {
        $response = $this->client->put("/api/access/users/$username", [
            RequestOptions::JSON => [
                'active' => $active,
                'admin' => $admin,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function change_password($username, $password)
    {
        $response = $this->client->put("/api/access/users/$username", [
            RequestOptions::JSON => [
                'password' => $password,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
