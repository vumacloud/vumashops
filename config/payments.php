<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    */

    'default' => env('PAYMENT_GATEWAY', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Available Payment Gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        /*
        |--------------------------------------------------------------------------
        | Paystack - Card Payments
        |--------------------------------------------------------------------------
        */

        'paystack' => [
            'name' => 'Paystack',
            'description' => 'Pay with Card (Visa, Mastercard, Verve)',
            'driver' => \App\Services\Payments\PaystackGateway::class,
            'enabled' => true,
            'countries' => ['NG', 'GH', 'ZA', 'KE'],
            'currencies' => ['NGN', 'GHS', 'ZAR', 'KES', 'USD'],
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
            'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Flutterwave - Card Payments
        |--------------------------------------------------------------------------
        */

        'flutterwave' => [
            'name' => 'Flutterwave',
            'description' => 'Pay with Card, Bank Transfer, or Mobile Money',
            'driver' => \App\Services\Payments\FlutterwaveGateway::class,
            'enabled' => true,
            'countries' => ['NG', 'GH', 'KE', 'UG', 'TZ', 'ZA', 'RW', 'ZM', 'CM', 'CI'],
            'currencies' => ['NGN', 'GHS', 'KES', 'UGX', 'TZS', 'ZAR', 'RWF', 'ZMW', 'XAF', 'XOF', 'USD', 'EUR', 'GBP'],
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
            'base_url' => 'https://api.flutterwave.com/v3',
        ],

        /*
        |--------------------------------------------------------------------------
        | M-Pesa Kenya (Safaricom Daraja API)
        |--------------------------------------------------------------------------
        */

        'mpesa_kenya' => [
            'name' => 'M-Pesa Kenya',
            'description' => 'Pay with M-Pesa (Kenya)',
            'driver' => \App\Services\Payments\MpesaKenyaGateway::class,
            'enabled' => true,
            'countries' => ['KE'],
            'currencies' => ['KES'],
            'environment' => env('MPESA_KENYA_ENV', 'sandbox'),
            'consumer_key' => env('MPESA_KENYA_CONSUMER_KEY'),
            'consumer_secret' => env('MPESA_KENYA_CONSUMER_SECRET'),
            'shortcode' => env('MPESA_KENYA_SHORTCODE'),
            'passkey' => env('MPESA_KENYA_PASSKEY'),
            'initiator_name' => env('MPESA_KENYA_INITIATOR_NAME'),
            'initiator_password' => env('MPESA_KENYA_INITIATOR_PASSWORD'),
            'callback_url' => env('MPESA_KENYA_CALLBACK_URL'),
            'timeout_url' => env('MPESA_KENYA_TIMEOUT_URL'),
            'result_url' => env('MPESA_KENYA_RESULT_URL'),
            'sandbox_url' => 'https://sandbox.safaricom.co.ke',
            'production_url' => 'https://api.safaricom.co.ke',
        ],

        /*
        |--------------------------------------------------------------------------
        | M-Pesa Tanzania (Vodacom)
        |--------------------------------------------------------------------------
        */

        'mpesa_tanzania' => [
            'name' => 'M-Pesa Tanzania',
            'description' => 'Pay with M-Pesa (Tanzania)',
            'driver' => \App\Services\Payments\MpesaTanzaniaGateway::class,
            'enabled' => true,
            'countries' => ['TZ'],
            'currencies' => ['TZS'],
            'environment' => env('MPESA_TANZANIA_ENV', 'sandbox'),
            'api_key' => env('MPESA_TANZANIA_API_KEY'),
            'public_key' => env('MPESA_TANZANIA_PUBLIC_KEY'),
            'service_provider_code' => env('MPESA_TANZANIA_SERVICE_PROVIDER_CODE'),
            'callback_url' => env('MPESA_TANZANIA_CALLBACK_URL'),
            'sandbox_url' => 'https://openapi.m-pesa.com:8443/sandbox',
            'production_url' => 'https://openapi.m-pesa.com:8443',
        ],

        /*
        |--------------------------------------------------------------------------
        | MTN Mobile Money
        |--------------------------------------------------------------------------
        */

        'mtn_momo' => [
            'name' => 'MTN Mobile Money',
            'description' => 'Pay with MTN Mobile Money',
            'driver' => \App\Services\Payments\MtnMomoGateway::class,
            'enabled' => true,
            'countries' => ['UG', 'GH', 'CI', 'CM', 'RW', 'ZM', 'BJ', 'CG', 'SZ'],
            'currencies' => ['UGX', 'GHS', 'XOF', 'XAF', 'RWF', 'ZMW', 'EUR'],
            'environment' => env('MTN_MOMO_ENV', 'sandbox'),
            'collection' => [
                'subscription_key' => env('MTN_MOMO_COLLECTION_SUBSCRIPTION_KEY'),
                'api_user' => env('MTN_MOMO_COLLECTION_API_USER'),
                'api_key' => env('MTN_MOMO_COLLECTION_API_KEY'),
            ],
            'disbursement' => [
                'subscription_key' => env('MTN_MOMO_DISBURSEMENT_SUBSCRIPTION_KEY'),
                'api_user' => env('MTN_MOMO_DISBURSEMENT_API_USER'),
                'api_key' => env('MTN_MOMO_DISBURSEMENT_API_KEY'),
            ],
            'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
            'provider_callback_host' => env('MTN_MOMO_PROVIDER_CALLBACK_HOST'),
            'currency' => env('MTN_MOMO_CURRENCY', 'EUR'),
            'sandbox_url' => 'https://sandbox.momodeveloper.mtn.com',
            'production_url' => 'https://momodeveloper.mtn.com',
        ],

        /*
        |--------------------------------------------------------------------------
        | Airtel Money
        |--------------------------------------------------------------------------
        */

        'airtel_money' => [
            'name' => 'Airtel Money',
            'description' => 'Pay with Airtel Money',
            'driver' => \App\Services\Payments\AirtelMoneyGateway::class,
            'enabled' => true,
            'countries' => ['UG', 'KE', 'TZ', 'RW', 'ZM', 'MW', 'NG', 'CG', 'CD', 'MG', 'NE', 'TD', 'GA', 'SL'],
            'currencies' => ['UGX', 'KES', 'TZS', 'RWF', 'ZMW', 'MWK', 'NGN', 'XAF', 'CDF', 'MGA', 'XOF', 'SLL'],
            'environment' => env('AIRTEL_MONEY_ENV', 'sandbox'),
            'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
            'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
            'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
            'country' => env('AIRTEL_MONEY_COUNTRY', 'UG'),
            'currency' => env('AIRTEL_MONEY_CURRENCY', 'UGX'),
            'sandbox_url' => 'https://openapiuat.airtel.africa',
            'production_url' => 'https://openapi.airtel.africa',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Webhook Verification
    |--------------------------------------------------------------------------
    */

    'verify_webhooks' => env('PAYMENT_VERIFY_WEBHOOKS', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => env('PAYMENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Transaction Reference Prefix
    |--------------------------------------------------------------------------
    */

    'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'VS'),

];
