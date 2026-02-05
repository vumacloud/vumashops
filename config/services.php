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

    /*
    |--------------------------------------------------------------------------
    | WHMCS Integration
    |--------------------------------------------------------------------------
    */
    'whmcs' => [
        'api_key' => env('WHMCS_API_KEY'),
        'url' => env('WHMCS_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Africa's Talking (SMS)
    |--------------------------------------------------------------------------
    */
    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    */
    'server' => [
        'ip' => env('SERVER_IP', '164.92.184.13'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Let's Encrypt SSL
    |--------------------------------------------------------------------------
    */
    'letsencrypt' => [
        'email' => env('LETSENCRYPT_EMAIL', 'admin@vumacloud.com'),
    ],

];
