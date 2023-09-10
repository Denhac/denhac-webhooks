<?php

return [

    /*
     * These directories will be scanned for projectors and reactors. They
     * will be registered to Projectionist automatically.
     */
    'auto_discover_projectors_and_reactors' => [
        app_path(),
    ],

    /*
     * Projectors are classes that build up projections. You can create them by performing
     * `php artisan event-sourcing:create-projector`. When not using auto-discovery,
     * Projectors can be registered in this array or a service provider.
     */
    'projectors' => [
        \App\Projectors\CardProjector::class,
        \App\Projectors\CardUpdateRequestProjector::class,
        \App\Projectors\CustomerProjector::class,
        \App\Projectors\SubscriptionProjector::class,
        \App\Projectors\WaiverProjector::class,
    ],

    /*
     * Reactors are classes that handle side-effects. You can create them by performing
     * `php artisan event-sourcing:create-reactor`. When not using auto-discovery
     * Reactors can be registered in this array or a service provider.
     */
    'reactors' => [
        \App\Reactors\CardNotifierReactor::class,
        \App\Reactors\GoogleGroupsReactor::class,
        \App\Reactors\SlackReactor::class,
        \App\Reactors\CardUpdateRequestReactor::class,
        \App\Reactors\GithubMembershipReactor::class,
        \App\Reactors\WaiverReactor::class,
    ],

    /*
     * A queue is used to guarantee that all events get passed to the projectors in
     * the right order. Here you can set of the name of the queue.
     */
    'queue' => env('EVENT_PROJECTOR_QUEUE_NAME', null),

    /*
     * When a Projector or Reactor throws an exception the event Projectionist can catch it
     * so all other projectors and reactors can still do their work. The exception will
     * be passed to the `handleException` method on that Projector or Reactor.
     */
    'catch_exceptions' => env('EVENT_PROJECTOR_CATCH_EXCEPTIONS', false),

    /*
     * This class is responsible for storing events in the EloquentStoredEventRepository.
     * To add extra behaviour you can change this to a class of your own. It should
     * extend the \Spatie\EventSourcing\Models\EloquentStoredEvent model.
     */
    'stored_event_model' => \Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent::class,

    /*
     * This class is responsible for storing events. To add extra behaviour you
     * can change this to a class of your own. The only restriction is that
     * it should implement \Spatie\EventSourcing\StoredEventRepository.
     */
    'stored_event_repository' => \Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository::class,

    /*
     * This class is responsible for handling stored events. To add extra behaviour you
     * can change this to a class of your own. The only restriction is that
     * it should implement \Spatie\EventSourcing\HandleDomainEventJob.
     */
    'stored_event_job' => \Spatie\EventSourcing\StoredEvents\HandleStoredEventJob::class,

    /*
     * Similar to Relation::morphMap() you can define which alias responds to which
     * event class. This allows you to change the namespace or classnames
     * of your events but still handle older events correctly.
     */
    'event_class_map' => [
        // Access Cards
        'App\StorableEvents\CardActivated' => 'App\StorableEvents\AccessCards\CardActivated',
        'App\StorableEvents\CardAdded' => 'App\StorableEvents\AccessCards\CardAdded',
        'App\StorableEvents\CardDeactivated' => 'App\StorableEvents\AccessCards\CardDeactivated',
        'App\StorableEvents\CardNotificationEmailNeeded' => 'App\StorableEvents\AccessCards\CardNotificationEmailNeeded',
        'App\StorableEvents\CardNotificationNeeded' => 'App\StorableEvents\AccessCards\CardNotificationNeeded',
        'App\StorableEvents\CardRemoved' => 'App\StorableEvents\AccessCards\CardRemoved',
        'App\StorableEvents\CardSentForActivation' => 'App\StorableEvents\AccessCards\CardSentForActivation',
        'App\StorableEvents\CardSentForDeactivation' => 'App\StorableEvents\AccessCards\CardSentForDeactivation',
        'App\StorableEvents\CardStatusUpdated' => 'App\StorableEvents\AccessCards\CardStatusUpdated',

        // WooCommerce
        'App\StorableEvents\CustomerCreated' => 'App\StorableEvents\WooCommerce\CustomerCreated',
        'App\StorableEvents\CustomerDeleted' => 'App\StorableEvents\WooCommerce\CustomerDeleted',
        'App\StorableEvents\CustomerImported' => 'App\StorableEvents\WooCommerce\CustomerImported',
        'App\StorableEvents\CustomerIsNoEventTestUser' => 'App\StorableEvents\WooCommerce\CustomerIsNoEventTestUser',
        'App\StorableEvents\CustomerUpdated' => 'App\StorableEvents\WooCommerce\CustomerUpdated',
        'App\StorableEvents\SubscriptionCreated' => 'App\StorableEvents\WooCommerce\SubscriptionCreated',
        'App\StorableEvents\SubscriptionDeleted' => 'App\StorableEvents\WooCommerce\SubscriptionDeleted',
        'App\StorableEvents\SubscriptionImported' => 'App\StorableEvents\WooCommerce\SubscriptionImported',
        'App\StorableEvents\SubscriptionUpdated' => 'App\StorableEvents\WooCommerce\SubscriptionUpdated',
        'App\StorableEvents\UserMembershipCreated' => 'App\StorableEvents\WooCommerce\UserMembershipCreated',
        'App\StorableEvents\UserMembershipDeleted' => 'App\StorableEvents\WooCommerce\UserMembershipDeleted',
        'App\StorableEvents\UserMembershipImported' => 'App\StorableEvents\WooCommerce\UserMembershipImported',
        'App\StorableEvents\UserMembershipUpdated' => 'App\StorableEvents\WooCommerce\UserMembershipUpdated',

        // GitHub
        'App\StorableEvents\GithubUsernameUpdated' => 'App\StorableEvents\GitHub\GitHubUsernameUpdated',

        // Membership
        'App\StorableEvents\IdWasChecked' => 'App\StorableEvents\Membership\IdWasChecked',
        'App\StorableEvents\MembershipActivated' => 'App\StorableEvents\Membership\MembershipActivated',
        'App\StorableEvents\MembershipDeactivated' => 'App\StorableEvents\Membership\MembershipDeactivated',

        // Waiver
        'App\StorableEvents\ManualBootstrapWaiverNeeded' => 'App\StorableEvents\Waiver\ManualBootstrapWaiverNeeded',
        'App\StorableEvents\WaiverAccepted' => 'App\StorableEvents\Waiver\WaiverAccepted',
        'App\StorableEvents\WaiverAssignedToCustomer' => 'App\StorableEvents\Waiver\WaiverAssignedToCustomer',
    ],

    /*
     * This class is responsible for serializing events. By default an event will be serialized
     * and stored as json. You can customize the class name. A valid serializer
     * should implement Spatie\EventSourcing\EventSerializers\Serializer.
     */
    'event_serializer' => \Spatie\EventSourcing\EventSerializers\JsonEventSerializer::class,

    /*
     * When replaying events, potentially a lot of events will have to be retrieved.
     * In order to avoid memory problems events will be retrieved as chunks.
     * You can specify the chunk size here.
     */
    'replay_chunk_size' => 1000,

    /*
     * In production, you likely don't want the package to auto-discover the event handlers
     * on every request. The package can cache all registered event handlers.
     * More info: https://docs.spatie.be/laravel-event-sourcing/v1/advanced-usage/discovering-projectors-and-reactors
     *
     * Here you can specify where the cache should be stored.
     */
    'cache_path' => storage_path('app/event-sourcing'),
];
