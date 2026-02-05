<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class TenantPaymentGateway extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'gateway',
        'display_name',
        'credentials',
        'is_active',
        'is_test_mode',
        'supported_currencies',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'supported_currencies' => 'array',
        'settings' => 'array',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Available payment gateways
     */
    public const GATEWAYS = [
        'paystack' => 'Paystack',
        'flutterwave' => 'Flutterwave',
        'mpesa' => 'M-Pesa (Kenya)',
        'mtn_momo' => 'MTN Mobile Money',
        'airtel_money' => 'Airtel Money',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
    ];

    /**
     * Gateway credential requirements
     */
    public const GATEWAY_CREDENTIALS = [
        'paystack' => ['public_key', 'secret_key'],
        'flutterwave' => ['public_key', 'secret_key', 'encryption_key'],
        'mpesa' => ['consumer_key', 'consumer_secret', 'passkey', 'shortcode', 'initiator_name', 'initiator_password'],
        'mtn_momo' => ['subscription_key', 'api_user', 'api_key', 'callback_url'],
        'airtel_money' => ['client_id', 'client_secret'],
        'stripe' => ['publishable_key', 'secret_key', 'webhook_secret'],
        'paypal' => ['client_id', 'client_secret'],
    ];

    /**
     * Get the tenant that owns this gateway
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Encrypt credentials when setting
     */
    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : [],
            set: fn ($value) => $value ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    /**
     * Get a specific credential
     */
    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials, $key, $default);
    }

    /**
     * Check if gateway is properly configured
     */
    public function isConfigured(): bool
    {
        $required = self::GATEWAY_CREDENTIALS[$this->gateway] ?? [];
        $credentials = $this->credentials;

        foreach ($required as $key) {
            if (empty($credentials[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get gateway display name
     */
    public static function getGatewayName(string $gateway): string
    {
        return self::GATEWAYS[$gateway] ?? $gateway;
    }

    /**
     * Scope to only active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
