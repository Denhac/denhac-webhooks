<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\EventSourcing\Projectionist;
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

    public function withOnlyEventHandler($cls)
    {
        $projectionist = $this->app->make(Projectionist::class);

        $handlersToRemove = $projectionist->getReactors()
            ->merge($projectionist->getProjectors())
            ->reject(function($value) use ($cls) {
                return get_class($value) == $cls;
            });

        $projectionist->withoutEventHandlers($handlersToRemove->keys()->all());
    }
}
