<?php

namespace App\External\QuickBooks\Webhooks;

use App\Actions\QuickBooks\PullCurrentlyUsedAmountForBudgetFromQuickBooks;
use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Models\Budget;
use Illuminate\Support\Collection;
use QuickBooksOnline\API\Data\IPPid;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\DataService\DataService;
use ReflectionClass;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessWebhookJob extends \Spatie\WebhookClient\Jobs\ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onQueue('webhooks');
    }

    public function handle()
    {
        $payload = $this->webhookCall->payload;

        if (! array_key_exists('eventNotifications', $payload)) {
            return;
        }

        $eventNotifications = $payload['eventNotifications'];

        $ourRealmId = QuickBooksAuthSettings::getRealmId();
        if (is_null($ourRealmId)) {
            return;  // If we have not authed with a server, we will ignore all events so exit early
        }

        foreach ($eventNotifications as $notification) {
            if (! array_key_exists('realmId', $notification) ||
                $notification['realmId'] != $ourRealmId) {
                continue;
            }

            if (! array_key_exists('dataChangeEvent', $notification) ||
                ! array_key_exists('entities', $notification['dataChangeEvent'])) {
                continue;
            }

            $entities = $notification['dataChangeEvent']['entities'];

            foreach ($entities as $entity) {
                $this->handleEntity($entity);
            }
        }
    }

    private function handleEntity(array $entityData): void
    {
        $id = $entityData['id'];
        $type = $entityData['name'];

        /** @var DataService $dataService */
        $dataService = app(DataService::class);

        /** @var IPPIntuitEntity $entity */
        $entity = $dataService->FindById($type, $id);

        $this->handleHasClasses($entity);
    }

    private function handleHasClasses(IPPIntuitEntity $entity): void
    {
        $classRefs = $this->classRefsFrom($entity);
        $allBudgets = Budget::all();

        foreach($classRefs as $classRef) {
            /** @var Budget $matchingBudget */
            $matchingBudget = $allBudgets->first(fn($budget) => $budget->quickbooks_class_id == $classRef);

            if(! is_null($matchingBudget)) {
                PullCurrentlyUsedAmountForBudgetFromQuickBooks::queue()->execute($matchingBudget);
            }
        }
    }

    /**
     * @param mixed $entity The recursive entity we're getting classes from.
     *
     * @return Collection
     */
    public static function classRefsFrom(mixed $entity)
    {
        $classes = collect();

        $rc = new ReflectionClass(get_class($entity));

        foreach ($rc->getProperties() as $property) {
            $propertyValue = $entity->{$property->name};

            if($property->name == 'ClassRef') {
                // id or reference type, either way we got the full reference and not just the string
                if($propertyValue instanceof IPPid) {
                    $propertyValue = $propertyValue->value;
                }

                if(! $classes->contains($propertyValue)) {
                    $classes->push($propertyValue);
                }
            } elseif(is_array($propertyValue)) {
                foreach($propertyValue as $value) {
                    $classes = $classes->union(self::classRefsFrom($value));
                }
            } elseif(is_object($propertyValue)) {
                $classes = $classes->union(self::classRefsFrom($propertyValue));
            }
        }

        return $classes->unique()->filter(fn($cr) => ! is_null($cr));
    }
}
