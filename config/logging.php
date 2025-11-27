<?php

return [

    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0664,
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack-events' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slack/events/events.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0664,
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack-interactive' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slack/interactive/interactive.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0664,
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'card-access' => [
            'driver' => 'daily',
            'path' => storage_path('logs/card-access/card-access.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0664,
            'days' => 14,
            'replace_placeholders' => true,
        ],
    ],

];
