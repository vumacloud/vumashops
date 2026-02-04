<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Tenant represents a hosted Bagisto store
 *
 * Each tenant gets:
 * - Their own Bagisto installation with dedicated MySQL database
 * - Bagisto GraphQL API at /graphql
 * - Next.js or default Bagisto storefront
 * - Custom domain with Let's Encrypt SSL
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Custom columns on tenants table (central database)
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone',
            'country',
            'currency',
            'timezone',
            'locale',
            'plan_id',
            'subscription_status',
            'subscription_ends_at',
            'trial_ends_at',
            'is_active',
            'whmcs_service_id',
            'whmcs_client_id',
            'suspended_at',
            'suspension_reason',
            // Bagisto installation
            'bagisto_path',
            'bagisto_database',
            'bagisto_version',
            'bagisto_installed_at',
            // Storefront
            'storefront_type', // bagisto_default, nextjs, nuxt
            // SSL
            'ssl_status',
            'ssl_issued_at',
            'ssl_expires_at',
            // Flexible storage
            'settings',
            'data',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'data' => 'array',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'ssl_issued_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'bagisto_installed_at' => 'datetime',
    ];

    /**
     * Get the plan for this tenant
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get payment gateways configured for this tenant
     */
    public function paymentGateways()
    {
        return $this->hasMany(TenantPaymentGateway::class);
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant subscription is active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isOnTrial()) {
            return true;
        }

        return $this->subscription_status === 'active'
            && $this->subscription_ends_at
            && $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended'
            || $this->suspended_at !== null;
    }

    /**
     * Suspend the tenant
     */
    public function suspend(string $reason = null): self
    {
        $this->update([
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
            'is_active' => false,
        ]);

        return $this;
    }

    /**
     * Unsuspend the tenant
     */
    public function unsuspend(): self
    {
        $this->update([
            'subscription_status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
            'is_active' => true,
        ]);

        return $this;
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();

        return $this;
    }

    /**
     * Get plan feature limit
     */
    public function getFeatureLimit(string $feature): int
    {
        if (!$this->plan) {
            return 0;
        }

        return data_get($this->plan->limits, $feature, 0);
    }

    /**
     * Check if tenant has feature
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false;
        }

        $value = data_get($this->plan->limits, $feature);

        if (is_bool($value)) {
            return $value;
        }

        return $value !== 0 && $value !== null;
    }

    /**
     * Get the primary domain
     */
    public function getPrimaryDomain(): ?string
    {
        return $this->domains()->first()?->domain;
    }

    /**
     * Check if Bagisto is installed for this tenant
     */
    public function isBagistoInstalled(): bool
    {
        return $this->bagisto_installed_at !== null;
    }

    /**
     * Get the Bagisto admin panel URL
     */
    public function getAdminUrl(): string
    {
        $domain = $this->getPrimaryDomain();
        return $domain ? "https://{$domain}/admin" : '#';
    }

    /**
     * Get the Bagisto GraphQL API URL
     */
    public function getApiUrl(): string
    {
        $domain = $this->getPrimaryDomain();
        return $domain ? "https://{$domain}/graphql" : '#';
    }

    /**
     * Get the storefront URL
     */
    public function getStorefrontUrl(): string
    {
        $domain = $this->getPrimaryDomain();
        return $domain ? "https://{$domain}" : '#';
    }

    /**
     * Get the Bagisto installation path
     */
    public function getBagistoPath(): string
    {
        return $this->bagisto_path ?? "/var/www/tenants/{$this->id}";
    }
}
