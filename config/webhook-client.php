<?php

// Instead of commenting each key, reference https://github.com/spatie/laravel-webhook-client#configuring-the-package
return [
    'configs' => [
        [
            'name' => 'denhac.org',
            'signing_secret' => env('DENHAC_ORG_SIGNING_SECRET'),
            'signature_header_name' => 'X-WC-Webhook-Signature',
            'signature_validator' => \App\External\WooCommerce\SignatureValidator::class,
            'webhook_profile' => \App\External\WooCommerce\WebhookProfile::class,
            'webhook_model' => \App\External\WooCommerce\WebhookCall::class,
            'process_webhook_job' => \App\External\WooCommerce\ProcessWebhookJob::class,
        ],
        [
            'name' => 'WaiverForever',
            'signing_secret' => env('WAIVER_FOREVER_SIGNING_SECRET'),
            'signature_header_name' => 'X-WaiverForever-Signature',
            'signature_validator' => \App\External\WaiverForever\SignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\External\WaiverForever\ProcessWebhookJob::class,
        ],
        [
            'name' => 'QuickBooks',
            'signing_secret' => env('QUICKBOOKS_VERIFIER_TOKEN'),
            'signature_header_name' => 'intuit-signature',
            'signature_validator' => \App\External\QuickBooks\Webhooks\SignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\External\QuickBooks\Webhooks\ProcessWebhookJob::class,
        ],
    ],
];
