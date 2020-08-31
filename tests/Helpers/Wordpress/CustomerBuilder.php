<?php

namespace Tests\Helpers\Wordpress;


/**
 * Class CustomerBuilder
 * @package Tests\Helpers\Wordpress
 * @property int id
 */
class CustomerBuilder extends BaseBuilder
{
    private $metaKeyId = 1;

    public function __construct()
    {
        $this->data = [
            "id" => 1,
            "date_created" => "2020-01-01T00:00:00",
            "date_created_gmt" => "2020-01-01T00:00:00",
            "date_modified" => "2020-01-01T00:00:00",
            "date_modified_gmt" => "2020-01-01T00:00:00",
            "email" => "email@example.com",
            "first_name" => "first_name",
            "last_name" => "last_name",
            "role" => "customer",
            "username" => "username",
            "billing" => [
                "first_name" => "first_name",
                "last_name" => "last_name",
                "company" => "",
                "address_1" => "1234 Main St.",
                "address_2" => "",
                "city" => "Denver",
                "postcode" => "80204",
                "country" => "US",
                "state" => "CO",
                "email" => "email@example.com",
                "phone" => "5550001234"
            ],
            "is_paying_customer" => false,
            "meta_data" => [],
        ];
    }

    public function id($id)
    {
        $this->data["id"] = $id;
        return $this;
    }

    public function meta_data($key, $value)
    {
        foreach($this->data["meta_data"] as $key => $item) {
            if($item["key"] == $key) {
                $this->data["meta_data"][$key]["value"] = $value;
                return $this;
            }
        }

        $this->data["meta_data"][] = [
            "id" => $this->metaKeyId,
            "key" => $key,
            "value" => $value,
        ];

        $this->metaKeyId++;

        return $this;
    }

    public function access_card($card)
    {
        return $this->meta_data('access_card_number', $card);
    }

    public function github_username($username)
    {
        return $this->meta_data('github_username', $username);
    }
}
