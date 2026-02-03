<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Providers\TenancyServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            TenancyServiceProvider::setTenant($tenant);
        }

        return $next($request);
    }

    /**
     * Resolve the tenant from the request.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // Check if this is a central domain
        if (in_array($host, $centralDomains)) {
            return null;
        }

        // Try to find tenant by domain
        $tenant = Tenant::where('domain', $host)
            ->orWhereHas('domains', function ($query) use ($host) {
                $query->where('domain', $host);
            })
            ->first();

        if ($tenant) {
            return $tenant;
        }

        // Try to find by subdomain
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            $tenant = Tenant::where('subdomain', $subdomain)->first();

            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }
}
