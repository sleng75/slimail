<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('SES_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('SES_SECRET', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('SES_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'configuration_set' => env('SES_CONFIGURATION_SET'),

        // Mock mode settings
        'mock_mode' => env('SES_MOCK_MODE', false),
        'force_production' => env('SES_FORCE_PRODUCTION', false),
        'mock_failure_rate' => env('SES_MOCK_FAILURE_RATE', 0), // 0-100, percentage of simulated failures
        'mock_log_content' => env('SES_MOCK_LOG_CONTENT', true), // Log email content in mock mode
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY', ''),
        'site_id' => env('CINETPAY_SITE_ID', ''),
        'secret_key' => env('CINETPAY_SECRET_KEY', ''),
        'sandbox' => env('CINETPAY_SANDBOX', true),
    ],

];
