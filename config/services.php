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

    'flexpay' => [
        'merchant' => env('FLEXPAY_MARCHAND'),
        'token' => env('FLEXPAY_API_TOKEN'),
        'gateway_mobile' => env('FLEXPAY_GATEWAY_MOBILE', 'https://backend.flexpay.cd/api/rest/v1/mobile'),
        'gateway_card' => env('FLEXPAY_GATEWAY_CARD', 'https://backend.flexpay.cd/api/rest/v1/card'),
        'gateway_check' => env('FLEXPAY_GATEWAY_CHECK', 'https://backend.flexpay.cd/api/rest/v1/check'),
    ],

    'sms' => [
        'notification_url' => env('SMS_NOTIFICATION_WEBHOOK_URL', env('SMS_URL')),
        'url' => env('SMS_URL'),
        'balance_url' => env('SMS_BALANCE_URL', env('BALANCE_URL')),
        'delivery_url' => env('SMS_DELIVERY_URL', 'https://api.keccel.com/sms/delivery.asp'),
        'token' => env('SMS_TOKEN'),
        'from' => env('SMS_FROM', 'CMP'),
        'timeout' => env('SMS_TIMEOUT', 15),
    ],

];
