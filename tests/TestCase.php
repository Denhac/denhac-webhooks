<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\Projectionist;
use Tests\Helpers\OctoPrintUpdateBuilder;
use Tests\Helpers\WaiverForever\WaiverBuilder;
use Tests\Helpers\Wordpress\CustomerBuilder;
use Tests\Helpers\Wordpress\SubscriptionBuilder;
use Tests\Helpers\Wordpress\UserMembershipBuilder;

/**
 * Class TestCase
 *
 * @property User apiUser
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();

        if (isset($uses[AssertsActions::class])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->setUpActionAssertion();
        }

        return $uses;
    }

    private $_apiUser;

    public function __get($key)
    {
        if ($key == 'apiUser') {
            if (is_null($this->_apiUser)) {
                $this->_apiUser = User::create([
                    'name' => 'Test User',
                ]);
            }

            return $this->_apiUser;
        }

        return null;
    }

    public function customer(): CustomerBuilder
    {
        return new CustomerBuilder();
    }

    public function subscription(): SubscriptionBuilder
    {
        return new SubscriptionBuilder();
    }

    public function userMembership(): UserMembershipBuilder
    {
        return new UserMembershipBuilder();
    }

    public function waiver(): WaiverBuilder
    {
        return new WaiverBuilder();
    }

    public static function subscriptionStatuses(): array
    {
        return [
            'Pending' => ['pending'],
            'Active' => ['active'],
            'On hold' => ['on-hold'],
            'Cancelled' => ['cancelled'],
            'Switched' => ['switched'],
            'Expired' => ['expired'],
            'Pending Cancellation' => ['pending-cancel'],
            'Suspended for Non Payment' => ['suspended-payment'],
            'Suspended Manually' => ['suspended-manual'],
        ];
    }

    public static function userMembershipStatuses(): array
    {
        return [
            'Active' => ['active'],
            'Free Trial' => ['free_trial'],
            'Delayed' => ['delayed'],
            'Complimentary' => ['complimentary'],
            'Pending Cancellation' => ['pending'],
            'Paused' => ['paused'],
            'Expired' => ['expired'],
            'Cancelled' => ['cancelled'],
        ];
    }

    public function octoPrintUpdate(): OctoPrintUpdateBuilder
    {
        return new OctoPrintUpdateBuilder();
    }

    public function withOnlyEventHandlerType($cls)
    {
        $projectionist = $this->app->make(Projectionist::class);

        $handlersToRemove = $projectionist->getReactors()
            ->merge($projectionist->getProjectors())
            ->reject(function ($value) use ($cls) {
                return get_class($value) == $cls;
            });

        $projectionist->withoutEventHandlers(collect($handlersToRemove)->keys()->all());
    }

    public function withEventHandlers(...$instances)
    {
        $projectionist = $this->app->make(Projectionist::class);

        $currentProjectors = collect($projectionist->getProjectors())->values();
        $currentReactors = collect($projectionist->getReactors())->values();

        $projectionist->withoutEventHandlers();

        $projectionist->addProjectors($this->filteredEventHandlers($currentProjectors, $instances));
        $projectionist->addReactors($this->filteredEventHandlers($currentReactors, $instances));
    }

    private function filteredEventHandlers(Collection $eventHandlers, $instances): array
    {
        $wanted_classes = [];

        foreach ($instances as $instance) {
            $wanted_classes[get_class($instance)] = $instance;
        }

        return $eventHandlers
            ->map(function ($value) use ($wanted_classes) {
                $cls = get_class($value);
                if (array_key_exists($cls, $wanted_classes)) {
                    return $wanted_classes[$cls];
                }

                return null;
            })
            ->reject(null)
            ->all();
    }
}
