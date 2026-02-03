<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'currency',
        'trial_days',
        'limits',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'trial_days' => 'integer',
        'limits' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'trial_days' => 14,
        'currency' => 'USD',
        'sort_order' => 0,
        'limits' => '{}',
        'features' => '[]',
        'metadata' => '{}',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getLimit(string $key, $default = 0)
    {
        return data_get($this->limits, $key, $default);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getYearlySavings(): float
    {
        $monthlyTotal = $this->monthly_price * 12;
        return $monthlyTotal - $this->yearly_price;
    }

    public function getYearlySavingsPercentage(): float
    {
        $monthlyTotal = $this->monthly_price * 12;
        if ($monthlyTotal == 0) return 0;
        return round((($monthlyTotal - $this->yearly_price) / $monthlyTotal) * 100, 1);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('monthly_price');
    }

    public static function getDefaultPlanLimits(): array
    {
        return [
            'products' => 50,
            'categories' => 10,
            'attributes' => 20,
            'attribute_families' => 5,
            'orders' => 100,
            'staff_accounts' => 2,
            'storage_mb' => 500,
            'custom_domain' => false,
            'api_access' => false,
            'priority_support' => false,
            'analytics' => 'basic',
            'export_data' => true,
            'import_data' => true,
            'bulk_operations' => false,
            'multiple_currencies' => false,
            'multiple_languages' => false,
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = \Illuminate\Support\Str::slug($plan->name);
            }
        });
    }
}
