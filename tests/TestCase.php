<?php

namespace Tests;

use App\Customer;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Spatie\EventSourcing\Projectionist;
use Spatie\QueueableAction\ActionJob;
use Tests\Helpers\ActionAssertion;
use Tests\Helpers\OctoPrintUpdateBuilder;
use Tests\Helpers\Wordpress\CustomerBuilder;
use Tests\Helpers\Wordpress\SubscriptionBuilder;
use Tests\Helpers\Wordpress\UserMembershipBuilder;

/**
 * Class TestCase
 * @package Tests
 * @property User apiUser
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    private $_apiUser;

    public function __get($key)
    {
        if ($key == "apiUser") {
            if (is_null($this->_apiUser)) {
                $this->_apiUser = User::create([
                    'name' => 'Test User'
                ]);
            }

            return $this->_apiUser;
        }
        return null;
    }

    public function customerModel(): Customer {
        return Customer::create([
            'username' => $this->faker->userName,
            'email' => $this->faker->email,
            'woo_id' => $this->faker->randomNumber(),
            'member' => $this->faker->boolean,
            'slack_id' => "U".$this->faker->numberBetween(1e5, 1e7)
        ]);
    }

    public function customer()
    {
        return new CustomerBuilder();
    }

    public function subscription()
    {
        return new SubscriptionBuilder();
    }

    public function userMembership()
    {
        return new UserMembershipBuilder();
    }

    public function subscriptionStatuses(): array
    {
        return [
            "Pending" => ["pending"],
            "Active" => ["active"],
            "On hold" => ["on-hold"],
            "Cancelled" => ["cancelled"],
            "Switched" => ["switched"],
            "Expired" => ["expired"],
            "Pending Cancellation" => ["pending-cancel"],
            "Need ID Check" => ["need-id-check"],
            "ID Checked" => ["id-was-checked"],
            "Suspended for Non Payment" => ["suspended-payment"],
            "Suspended Manually" => ["suspended-manual"],
        ];
    }

    public function userMembershipStatuses(): array
    {
        return [
            "Active" => ["active"],
            "Free Trial" => ["free_trial"],
            "Delayed" => ["delayed"],
            "Complimentary" => ["complimentary"],
            "Pending Cancellation" => ["pending"],
            "Paused" => ["paused"],
            "Expired" => ["expired"],
            "Cancelled" => ["cancelled"],
        ];
    }

    public function octoPrintUpdate()
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

    public function assertActionPushed($cls): ActionAssertion {
        $jobs = Queue::pushedJobs();
        if(! array_key_exists(ActionJob::class, $jobs)) {
            $this->fail("No action jobs were pushed");
        }

        $actionJobs = collect($jobs[ActionJob::class])
            ->map(fn($actionJob) => $actionJob['job'])
            ->filter(fn($actionJob) => $actionJob->displayName() == $cls);

        if($actionJobs->count() == 0) {
            $this->fail("$cls was not pushed");
        } else if($actionJobs->count() > 1) {
            $this->fail("$cls had more than one job pushed which we don't support yet");
        };

        return new ActionAssertion($actionJobs->first());
    }
}
