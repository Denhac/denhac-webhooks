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
        'auth_as' => env('GOOGLE_API_AUTH_AS_USER'),
    ],
    'slack' => [
        'spacebot_api_token' => env('SLACK_SPACEBOT_API_TOKEN'),
        'management_api_token' => env('SLACK_MANAGEMENT_API_TOKEN'),
        'spacebot_api_signing_secret' => env('SLACK_SPACEBOT_API_SIGNING_SECRET'),
        'management_api_signing_secret' => env('SLACK_MANAGEMENT_API_SIGNING_SECRET'),
        'email' => env('SLACK_ADMIN_API_EMAIL'),
        'password' => env('SLACK_ADMIN_API_PASSWORD'),
        'team_id' => env('SLACK_TEAM_ID')
    ],
    'github' => [
        'key_path' => env('GITHUB_API_KEY_PATH', storage_path('github-api.pem')),
        'app_id' => env('GITHUB_APP_ID'),
        'installation_id' => env('GITHUB_INSTALLATION_ID'),
    ],
    'access_email' => env('ACCESS_EMAIL'),
    'notifications' => [
        'card_notification' => [
            'to' => env('NOTIFICATION_CARD_TO'),
        ],
        'slack' => [
        ],
    ],
    'waiver' => [
        'membership_waiver_template_id' => env('MEMBERSHIP_WAIVER_TEMPLATE_ID'),
        'membership_waiver_template_version' => env('MEMBERSHIP_WAIVER_TEMPLATE_VERSION'),
    ],
    'stripe' => [
        'stripe_api_key' => env('STRIPE_API_KEY'),
    ],
    'quickbooks' => [
        'client_id' => env('QUICKBOOKS_CLIENT_ID'),
        'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
        'redirect' => env('QUICKBOOKS_REDIRECT_URL'),
        'base_url' => env('QUICKBOOKS_BASE_URL'),
    ],
];
