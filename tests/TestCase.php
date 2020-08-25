<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Helpers\Wordpress\CustomerBuilder;
use Tests\Helpers\Wordpress\SubscriptionBuilder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function customer()
    {
        return new CustomerBuilder();
    }

    public function subscription()
    {
        return new SubscriptionBuilder();
    }
}
