<?php
return [
    'url' => env('DENHAC_ORG_URL'),
    'rest' => [
        'key' => env('DENHAC_WOOCOMMERCE_CONSUMER_KEY'),
        'secret' => env('DENHAC_WOOCOMMERCE_CONSUMER_SECRET'),
    ],
    'webhook_secret' => env('DENHAC_ORG_SIGNING_SECRET'),
    'google' => [
        'key_path' => env('GOOGLE_API_KEY_PATH', storage_path('google-api.pem')),
        'service_account' => env('GOOGLE_API_SERVICE_ACCOUNT'),
        'auth_as' => env('GOOGLE_API_AUTH_AS_USER')
    ],
    'slack' => [
        'api_token' => env('SLACK_API_TOKEN'),
        'email' => env('SLACK_API_EMAIL'),
        'password' => env('SLACK_API_PASSWORD'),
    ],
    'github' => [
        'key_path' => env('GITHUB_API_KEY_PATH', storage_path('github-api.pem')),
        'app_id' => env('GITHUB_APP_ID'),
        'installation_id' => env('GITHUB_INSTALLATION_ID'),
    ]
];
