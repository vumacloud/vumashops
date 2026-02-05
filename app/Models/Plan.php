<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'limits',
        'features',
        'is_active',
        'is_featured',
        'trial_days',
        'sort_order',
        'whmcs_product_id',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'limits' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get all tenants on this plan
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Scope to only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get a specific limit value
     */
    public function getLimit(string $key, $default = 0)
    {
        return data_get($this->limits, $key, $default);
    }

    /**
     * Check if plan has a feature
     */
    public function hasFeature(string $key): bool
    {
        $value = data_get($this->limits, $key);

        if (is_bool($value)) {
            return $value;
        }

        return $value !== 0 && $value !== null;
    }

    /**
     * Get yearly discount percentage
     */
    public function getYearlyDiscountAttribute(): float
    {
        if (!$this->price_monthly || !$this->price_yearly) {
            return 0;
        }

        $yearlyAtMonthlyRate = $this->price_monthly * 12;
        $savings = $yearlyAtMonthlyRate - $this->price_yearly;

        return round(($savings / $yearlyAtMonthlyRate) * 100, 1);
    }
}
