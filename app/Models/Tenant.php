<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

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
            'theme',
            'logo',
            'favicon',
            'plan_id',
            'subscription_status',
            'subscription_ends_at',
            'trial_ends_at',
            'is_active',
            'whmcs_service_id',
            'whmcs_client_id',
            'suspended_at',
            'suspension_reason',
            'ssl_status',
            'ssl_issued_at',
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
}
