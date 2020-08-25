<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Helpers\Wordpress\CustomerBuilder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function customer()
    {
        return new CustomerBuilder();
    }
}
