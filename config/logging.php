<?php

return [

    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0644,
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack-events' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slack-events.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0644,
            'days' => 14,
            'replace_placeholders' => true,
        ],
    ],

];
