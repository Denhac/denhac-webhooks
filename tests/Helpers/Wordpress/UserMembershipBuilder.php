<?php

namespace Tests\Helpers\Wordpress;

use Tests\Helpers\BaseBuilder;

/**
 * Class CustomerBuilder.
 * @property int id
 */
class UserMembershipBuilder extends BaseBuilder
{
    use HasMetaData;

    public function __construct()
    {
        $this->data = [
            "id" => 1,
            "status" => "active",
            "plan_id" => 1,
            "end_date" => null,
            "order_id" => null,
            "view_url" => "https://denhac.org/my-account/members-area/1/my-membership-content/",
            "meta_data" => [],
            "product_id" => null,
            "start_date" => '2020-01-01T00:00:00',
            "customer_id" => 1,
            "paused_date" => null,
            "date_created" => '2020-01-01T00:00:00',
            "end_date_gmt" => null,
            "cancelled_date" => null,
            "start_date_gmt" => '2020-01-01T00:00:00',
            "paused_date_gmt" => null,
            "subscription_id" => null,
            "date_created_gmt" => '2020-01-01T00:00:00',
            "cancelled_date_gmt" => null,
        ];
    }

    public function id($id): static
    {
        $this->data['id'] = $id;

        return $this;
    }

    public function status($status): static
    {
        $this->data['status'] = $status;

        return $this;
    }

    public function plan($plan): static
    {
        $this->data['plan_id'] = $plan;

        return $this;
    }

    public function customer($customer): static
    {
        if (is_int($customer)) {
            $this->data['customer_id'] = $customer;
        } else {
            $this->data['customer_id'] = $customer->id;
        }

        return $this;
    }

    public function team($teamId): static
    {
        return $this->meta_data('_team_id', $teamId);
    }
}
