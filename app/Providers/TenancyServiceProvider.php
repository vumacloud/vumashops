<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('current.tenant', function () {
            return null;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share current tenant with all views
        View::composer('*', function ($view) {
            $view->with('currentTenant', app('current.tenant'));
        });
    }

    /**
     * Set the current tenant.
     */
    public static function setTenant(?Tenant $tenant): void
    {
        app()->instance('current.tenant', $tenant);

        if ($tenant) {
            session(['tenant_id' => $tenant->id]);

            // Set locale
            app()->setLocale($tenant->locale ?? 'en');

            // Set timezone
            config(['app.timezone' => $tenant->timezone ?? 'Africa/Nairobi']);
        }
    }

    /**
     * Get the current tenant.
     */
    public static function getTenant(): ?Tenant
    {
        return app('current.tenant');
    }

    /**
     * Check if we're in a tenant context.
     */
    public static function hasTenant(): bool
    {
        return app('current.tenant') !== null;
    }
}
