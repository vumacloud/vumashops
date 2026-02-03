<?php

/**
 * Third-party Services Configuration
 *
 * VumaShops by VumaCloud
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare DNS Management
    |--------------------------------------------------------------------------
    |
    | Used for automating DNS configuration for vendor custom domains.
    | All vendors MUST use custom domains - no vumacloud.com subdomains allowed.
    |
    */

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'server_ip' => env('CLOUDFLARE_SERVER_IP', env('SERVER_IP')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Postmark / Mailgun (fallback)
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Services (for S3 storage)
    |--------------------------------------------------------------------------
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DigitalOcean Spaces (alternative storage)
    |--------------------------------------------------------------------------
    */

    'digitalocean' => [
        'spaces' => [
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'nyc3'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'url' => env('DO_SPACES_URL'),
            'endpoint' => env('DO_SPACES_ENDPOINT'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications (for admin alerts)
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Services
    |--------------------------------------------------------------------------
    */

    'google' => [
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'tag_manager_id' => env('GOOGLE_TAG_MANAGER_ID'),
        'recaptcha' => [
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook / Meta
    |--------------------------------------------------------------------------
    */

    'facebook' => [
        'pixel_id' => env('FACEBOOK_PIXEL_ID'),
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
    ],

];
