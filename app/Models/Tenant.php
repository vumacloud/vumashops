<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tenant extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'domain',
        'subdomain',
        'logo',
        'favicon',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'currency',
        'timezone',
        'locale',
        'plan_id',
        'subscription_status',
        'subscription_ends_at',
        'trial_ends_at',
        'is_active',
        'settings',
        'metadata',
        'whmcs_service_id',
        'whmcs_client_id',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'subscription_status' => 'trial',
        'settings' => '{}',
        'metadata' => '{}',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(TenantPaymentGateway::class);
    }

    public function paymentGateways(): HasMany
    {
        return $this->hasMany(TenantPaymentGateway::class);
    }

    /**
     * Get enabled payment gateways for this tenant.
     */
    public function getEnabledPaymentGateways()
    {
        return $this->paymentGateways()->enabled()->configured()->ordered()->get();
    }

    /**
     * Get a specific payment gateway configuration.
     */
    public function getPaymentGateway(string $gateway): ?TenantPaymentGateway
    {
        return $this->paymentGateways()->where('gateway', $gateway)->first();
    }

    /**
     * Check if a payment gateway is enabled and configured.
     */
    public function hasPaymentGateway(string $gateway): bool
    {
        $pg = $this->getPaymentGateway($gateway);
        return $pg && $pg->is_enabled && $pg->isConfigured();
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function getFullDomainAttribute(): string
    {
        if ($this->domain) {
            return $this->domain;
        }

        return $this->subdomain . '.' . config('app.url');
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended' || $this->suspended_at !== null;
    }

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

    public function isSubscriptionActive(): bool
    {
        if ($this->isOnTrial()) {
            return true;
        }

        return $this->subscription_status === 'active' &&
               $this->subscription_ends_at &&
               $this->subscription_ends_at->isFuture();
    }

    public function hasFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false;
        }

        $limits = $this->plan->limits ?? [];
        return isset($limits[$feature]) && $limits[$feature] !== false;
    }

    public function getFeatureLimit(string $feature): int
    {
        if (!$this->plan) {
            return 0;
        }

        $limits = $this->plan->limits ?? [];
        return $limits[$feature] ?? 0;
    }

    public function canAddProduct(): bool
    {
        $limit = $this->getFeatureLimit('products');
        if ($limit === -1) return true; // unlimited
        return $this->products()->count() < $limit;
    }

    public function canAddCategory(): bool
    {
        $limit = $this->getFeatureLimit('categories');
        if ($limit === -1) return true;
        return $this->categories()->count() < $limit;
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return data_get($settings, $key, $default);
    }

    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        return $this;
    }

    public function getCurrencySymbol(): string
    {
        $currencies = config('app.supported_currencies', []);
        return $currencies[$this->currency]['symbol'] ?? $this->currency;
    }

    public function formatPrice($amount): string
    {
        $currencies = config('app.supported_currencies', []);
        $currencyConfig = $currencies[$this->currency] ?? ['symbol' => $this->currency, 'decimals' => 2];

        return $currencyConfig['symbol'] . ' ' . number_format($amount, $currencyConfig['decimals']);
    }
}
