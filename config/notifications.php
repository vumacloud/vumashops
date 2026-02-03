<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Provider - Brevo SMTP (formerly Sendinblue)
    |--------------------------------------------------------------------------
    | Email is sent via Laravel's built-in Mail facade using SMTP.
    | Configure SMTP settings in .env:
    | MAIL_MAILER=smtp
    | MAIL_HOST=smtp-relay.brevo.com
    | MAIL_PORT=587
    | MAIL_USERNAME=your-brevo-smtp-login
    | MAIL_PASSWORD=your-brevo-smtp-password
    | MAIL_ENCRYPTION=tls
    */

    'email' => [
        'provider' => 'smtp',
        'enabled' => true,
        'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@vumacloud.com'),
        'from_name' => env('MAIL_FROM_NAME', 'VumaShops'),
        'reply_to' => env('MAIL_REPLY_TO', 'support@vumacloud.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Provider - Africa's Talking
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'provider' => 'africastalking',
        'enabled' => true,

        'africastalking' => [
            'username' => env('AFRICASTALKING_USERNAME', 'sandbox'),
            'api_key' => env('AFRICASTALKING_API_KEY'),
            'from' => env('AFRICASTALKING_FROM', 'VumaShops'),
            'sandbox' => env('AFRICASTALKING_SANDBOX', true),
        ],

        'templates' => [
            'order_confirmation' => 'Hi {customer_name}, your order #{order_number} has been confirmed. Total: {currency} {total}. Track at: {tracking_url}',
            'order_shipped' => 'Hi {customer_name}, your order #{order_number} has been shipped. Expected delivery: {delivery_date}. Track at: {tracking_url}',
            'order_delivered' => 'Hi {customer_name}, your order #{order_number} has been delivered. Thank you for shopping with us!',
            'payment_received' => 'Hi {customer_name}, we have received your payment of {currency} {amount} for order #{order_number}. Thank you!',
            'payment_reminder' => 'Hi {customer_name}, your payment of {currency} {amount} for order #{order_number} is pending. Pay now: {payment_url}',
            'otp_verification' => 'Your VumaShops verification code is: {otp}. Valid for {validity} minutes.',
            'password_reset' => 'Your VumaShops password reset code is: {code}. Valid for {validity} minutes.',
            'welcome' => 'Welcome to {store_name}! Thank you for signing up. Start shopping at: {store_url}',
            'low_stock_alert' => 'Alert: {product_name} is running low on stock ({quantity} remaining). Restock soon!',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications (via Africa's Talking)
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'provider' => 'africastalking',
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notifications
    |--------------------------------------------------------------------------
    */

    'push' => [
        'enabled' => env('PUSH_ENABLED', false),
        'provider' => 'firebase',
        'firebase' => [
            'credentials_path' => storage_path('firebase/credentials.json'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Events
    |--------------------------------------------------------------------------
    */

    'events' => [
        'order_placed' => ['email', 'sms'],
        'order_confirmed' => ['email', 'sms'],
        'order_shipped' => ['email', 'sms'],
        'order_delivered' => ['email', 'sms'],
        'order_cancelled' => ['email', 'sms'],
        'payment_received' => ['email', 'sms'],
        'payment_failed' => ['email', 'sms'],
        'refund_processed' => ['email', 'sms'],
        'password_reset' => ['email'],
        'email_verification' => ['email'],
        'welcome' => ['email', 'sms'],
        'low_stock' => ['email'],
        'subscription_activated' => ['email'],
        'subscription_expired' => ['email', 'sms'],
        'new_customer' => ['email'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'sms_per_customer_per_day' => 10,
        'email_per_customer_per_hour' => 20,
    ],

];
