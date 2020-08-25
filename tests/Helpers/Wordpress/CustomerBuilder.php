<?php

namespace Tests\Helpers\Wordpress;


/**
 * Class CustomerBuilder
 * @package Tests\Helpers\Wordpress
 * @property int id
 */
class CustomerBuilder extends BaseBuilder
{
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
}
