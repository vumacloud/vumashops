<?php

namespace App\Http\Middleware;

use App\Providers\TenancyServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!TenancyServiceProvider::hasTenant()) {
            abort(404, 'Store not found.');
        }

        $tenant = TenancyServiceProvider::getTenant();

        // Check if tenant is active
        if (!$tenant->is_active) {
            abort(503, 'This store is currently unavailable.');
        }

        // Check subscription status
        if (!$tenant->isSubscriptionActive() && !$tenant->isOnTrial()) {
            abort(402, 'Subscription expired. Please renew your subscription.');
        }

        return $next($request);
    }
}
