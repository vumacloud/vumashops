<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'VumaShops'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */

    'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | VumaShops Specific Configuration
    |--------------------------------------------------------------------------
    */

    'currency' => env('APP_CURRENCY', 'KES'),

    'supported_currencies' => [
        'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'decimals' => 2],
        'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'decimals' => 0],
        'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh', 'decimals' => 0],
        'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'decimals' => 2],
        'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'decimals' => 2],
        'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'decimals' => 2],
        'RWF' => ['name' => 'Rwandan Franc', 'symbol' => 'FRw', 'decimals' => 0],
        'XOF' => ['name' => 'CFA Franc BCEAO', 'symbol' => 'CFA', 'decimals' => 0],
        'XAF' => ['name' => 'CFA Franc BEAC', 'symbol' => 'FCFA', 'decimals' => 0],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
    ],

    'supported_countries' => [
        'KE' => 'Kenya',
        'TZ' => 'Tanzania',
        'UG' => 'Uganda',
        'NG' => 'Nigeria',
        'GH' => 'Ghana',
        'ZA' => 'South Africa',
        'RW' => 'Rwanda',
        'ET' => 'Ethiopia',
        'SN' => 'Senegal',
        'CI' => 'Ivory Coast',
        'CM' => 'Cameroon',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
        'MW' => 'Malawi',
        'BW' => 'Botswana',
    ],

];
