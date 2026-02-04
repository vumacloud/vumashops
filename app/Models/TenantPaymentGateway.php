<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class TenantPaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'gateway',
        'is_enabled',
        'is_test_mode',
        'credentials',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'settings' => 'array',
        'sort_order' => 'integer',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Available payment gateways.
     */
    public const GATEWAYS = [
        'paystack' => [
            'name' => 'Paystack',
            'description' => 'Accept card payments (Nigeria, Ghana, South Africa, Kenya)',
            'credentials_schema' => ['public_key', 'secret_key'],
            'countries' => ['NG', 'GH', 'ZA', 'KE'],
        ],
        'flutterwave' => [
            'name' => 'Flutterwave',
            'description' => 'Accept card and mobile money payments across Africa',
            'credentials_schema' => ['public_key', 'secret_key', 'encryption_key'],
            'countries' => ['NG', 'GH', 'KE', 'ZA', 'TZ', 'UG', 'RW', 'ZM'],
        ],
        'mpesa_kenya' => [
            'name' => 'M-Pesa Kenya',
            'description' => 'Safaricom M-Pesa (Kenya)',
            'credentials_schema' => ['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'initiator_name', 'initiator_password'],
            'countries' => ['KE'],
        ],
        'mpesa_tanzania' => [
            'name' => 'M-Pesa Tanzania',
            'description' => 'Vodacom M-Pesa (Tanzania)',
            'credentials_schema' => ['api_key', 'public_key', 'service_provider_code'],
            'countries' => ['TZ'],
        ],
        'mtn_momo' => [
            'name' => 'MTN Mobile Money',
            'description' => 'MTN MoMo (Uganda, Ghana, Rwanda, Zambia)',
            'credentials_schema' => ['collection_subscription_key', 'collection_api_user', 'collection_api_key'],
            'countries' => ['UG', 'GH', 'RW', 'ZM'],
        ],
        'airtel_money' => [
            'name' => 'Airtel Money',
            'description' => 'Airtel Money (Uganda, Kenya, Tanzania, Rwanda)',
            'credentials_schema' => ['client_id', 'client_secret'],
            'countries' => ['UG', 'KE', 'TZ', 'RW'],
        ],
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Set credentials (encrypted).
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->attributes['credentials'] = Crypt::encryptString($value);
    }

    /**
     * Get credentials (decrypted).
     */
    public function getCredentialsAttribute($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get a specific credential value.
     */
    public function getCredential(string $key, $default = null)
    {
        $credentials = $this->credentials;
        return $credentials[$key] ?? $default;
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Check if gateway is properly configured.
     */
    public function isConfigured(): bool
    {
        $credentials = $this->credentials;
        if (empty($credentials)) {
            return false;
        }

        $schema = self::GATEWAYS[$this->gateway]['credentials_schema'] ?? [];
        foreach ($schema as $field) {
            if (empty($credentials[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get gateway info.
     */
    public function getGatewayInfo(): array
    {
        return self::GATEWAYS[$this->gateway] ?? [
            'name' => ucfirst($this->gateway),
            'description' => '',
            'credentials_schema' => [],
            'countries' => [],
        ];
    }

    /**
     * Scope: only enabled gateways.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: only configured gateways.
     */
    public function scopeConfigured($query)
    {
        return $query->whereNotNull('credentials');
    }

    /**
     * Scope: ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
