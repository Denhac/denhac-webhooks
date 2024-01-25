<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class FixEventSourcingAggregateVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event-sourcing:fix-aggregate-version {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix aggregate versions in our stored event, in case someone deleted an event';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->line('Dry run, will not actually update anything.');
        }

        $uuidToVersion = collect();

        $numModels = EloquentStoredEvent::count();
        $bar = $this->output->createProgressBar($numModels);

        $messages = collect();

        foreach (EloquentStoredEvent::orderBy('id')->lazy() as $event) {
            $bar->advance();
            /** @var EloquentStoredEvent $event */
            $aggregateUuid = $event->aggregate_uuid;
            if (empty($aggregateUuid)) {
                // We clear the meta data just in case, but it's probably empty
                /** @var SchemalessAttributes $metaData */
                $metaData = $event->meta_data;
                $originalCount = $metaData->count();
                $metaData->forget([
                    'aggregate-root-uuid',
                    'aggregate-root-version'
                ]);
                $newCount = $metaData->count();

                if ($isDryRun) {
                    if ($originalCount != $newCount) {
                        $messages->add("Would remove aggregate uuid metadata for $event->id");
                    }
                    continue;
                }

                $event->setAttribute('meta_data', $metaData);
                $event->aggregate_version = null;
                $event->save();

                continue;
            }

            // At this point, we know we have an aggregate uuid
            if (! $uuidToVersion->has($aggregateUuid)) {
                $uuidToVersion->put($aggregateUuid, 0);
            }

            $aggregateVersion = $uuidToVersion->get($aggregateUuid) + 1;
            $uuidToVersion->put($aggregateUuid, $aggregateVersion);

            if ($event->aggregate_version != $aggregateVersion) {
                if ($isDryRun) {
                    $messages->add("Would update aggregate version for event $event->id, uuid {$aggregateUuid} to $aggregateVersion");
                    continue;
                }

                $messages->add("Updating aggregate version for event $event->id, uuid {$aggregateUuid} to $aggregateVersion");
            }

            /** @var SchemalessAttributes $metaData */
            $metaData = $event->meta_data;
            $metaData->set('aggregate-root-uuid', $aggregateUuid);
            $metaData->set('aggregate-root-version', $aggregateVersion);
            $event->setAttribute('meta_data', $metaData);

            $event->aggregate_version = $aggregateVersion;
            $event->save();
        }

        $bar->finish();

        foreach ($messages as $message) {
            $this->info($message);
        }
    }
}
