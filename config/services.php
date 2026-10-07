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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'key_id'         => env('RAZORPAY_KEY_ID'),
        'key_secret'     => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'cashfree' => [
        'app_id'     => env('CASHFREE_APP_ID'),
        'secret_key' => env('CASHFREE_SECRET_KEY'), // also signs Cashfree webhooks
        'env'        => env('CASHFREE_ENV', 'sandbox'), // sandbox | production
    ],

    'ccavenue' => [
        'merchant_id' => env('CCAVENUE_MERCHANT_ID'),
        'access_code' => env('CCAVENUE_ACCESS_CODE'),
        'working_key' => env('CCAVENUE_WORKING_KEY'), // encrypts/decrypts every request and response
        'env'         => env('CCAVENUE_ENV', 'test'), // test | production
    ],

    'node_service_token' => env('NODE_SERVICE_TOKEN'),

];
