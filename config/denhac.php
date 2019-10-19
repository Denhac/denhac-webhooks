<?php
return [
    'url' => env('DENHAC_ORG_URL'),
    'rest' => [
        'key' => env('DENHAC_WOOCOMMERCE_CONSUMER_KEY'),
        'secret' => env('DENHAC_WOOCOMMERCE_CONSUMER_SECRET'),
    ],
    'webhook_secret' => env('DENHAC_ORG_SIGNING_SECRET'),
];
