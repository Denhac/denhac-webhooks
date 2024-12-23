<?php

return [

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ],

        'customer_api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ],
    ],

    'providers' => [
        'customers' => [
            'driver' => 'database',
            'table' => App\Models\Customer::class,
        ],
    ],

];
