<?php

namespace Tests\Helpers\Wordpress;

/**
 * Class SubscriptionBuilder.
 * @property int id
 * @property string status
 * @property string customer_id
 */
class SubscriptionBuilder extends BaseBuilder
{
    public function __construct()
    {
        $this->data = [
            'id' => 2,
            'parent_id' => 1,
            'status' => 'pending',
            'number' => '2',
            'currency' => 'USD',
            'version' => '4.3.2',
            'prices_include_tax' => false,
            'date_created' => '2020-01-01T00:00:00',
            'date_modified' => '2020-01-01T00:00:00',
            'customer_id' => 1,
            'discount_total' => '0.00',
            'discount_tax' => '0.00',
            'shipping_total' => '0.00',
            'shipping_tax' => '0.00',
            'cart_tax' => '0.00',
            'total' => '45.00',
            'total_tax' => '0.00',
            'billing' => [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'company' => '',
                'address_1' => '1234 Main St.',
                'address_2' => '',
                'city' => 'Denver',
                'state' => 'CO',
                'postcode' => '80204',
                'country' => 'US',
                'email' => 'email@example.com',
                'phone' => '5550001234',
            ],
            'payment_method' => 'stripe',
            'payment_method_title' => 'Credit Card (Stripe)',
            'transaction_id' => '',
            'created_via' => 'checkout',
            'customer_note' => '',
            'date_completed' => '2020-01-01T00:00:00',
            'date_paid' => '2020-01-01T00:00:00',
            'cart_hash' => '',
            'line_items' => [
                [
                    'id' => 125,
                    'name' => 'Monthly Membership - Regular',
                    'sku' => '',
                    'product_id' => 50,
                    'variation_id' => 313,
                    'quantity' => 1,
                    'tax_class' => '',
                    'price' => '45.00',
                    'subtotal' => '45.00',
                    'subtotal_tax' => '0.00',
                    'total' => '45.00',
                    'total_tax' => '0.00',
                    'taxes' => [],
                    'meta' => [],
                ],
            ],
            'tax_lines' => [],
            'shipping_lines' => [],
            'fee_lines' => [],
            'coupon_lines' => [],
            'refunds' => [],
            'billing_period' => 'month',
            'billing_interval' => '1',
            'resubscribed_from' => '',
            'resubscribed_subscription' => '',
            'start_date' => '2020-01-01T00:00:00',
            'trial_end_date' => '',
            'next_payment_date' => '2020-02-01T00:00:00',
            'end_date' => '',
            'date_completed_gmt' => '2020-01-01T00:00:00',
            'date_paid_gmt' => '2020-01-01T00:00:00',
            'removed_line_items' => [],
        ];
    }

    public function id($id)
    {
        $this->data['id'] = $id;

        return $this;
    }

    public function status($status)
    {
        $this->data['status'] = $status;

        return $this;
    }

    public function customer($customer)
    {
        if (is_int($customer)) {
            $this->data['customer_id'] = $customer;
        } else {
            $this->data['customer_id'] = $customer->id;
        }

        return $this;
    }
}
