<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Helpers\Wordpress\CustomerBuilder;
use Tests\Helpers\Wordpress\SubscriptionBuilder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    public function customer()
    {
        return new CustomerBuilder();
    }

    public function subscription()
    {
        return new SubscriptionBuilder();
    }
}
