<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically scope queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = static::getCurrentTenantId()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        // Automatically set tenant_id on creation
        static::creating(function (Model $model) {
            if (!$model->tenant_id && $tenantId = static::getCurrentTenantId()) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    /**
     * Get the current tenant ID from the application context.
     */
    protected static function getCurrentTenantId(): ?int
    {
        // Try to get from the tenant service
        if (app()->bound('current.tenant')) {
            $tenant = app('current.tenant');
            return $tenant?->id;
        }

        // Try to get from session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        // Try to get from authenticated admin
        if (auth('admin')->check() && auth('admin')->user()->tenant_id) {
            return auth('admin')->user()->tenant_id;
        }

        return null;
    }

    /**
     * Relationship to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to a specific tenant.
     */
    public function scopeForTenant(Builder $query, int|Tenant $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }

    /**
     * Scope to query without tenant restriction.
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope to all tenants (alias for withoutTenantScope).
     */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
