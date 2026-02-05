<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\BagistoProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhmcsProvisioningController extends Controller
{
    public function __construct(
        protected BagistoProvisioner $provisioner
    ) {
        $this->middleware(function ($request, $next) {
            $apiKey = $request->header('X-WHMCS-API-Key') ?? $request->input('api_key');

            if ($apiKey !== config('services.whmcs.api_key')) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Invalid API key',
                ], 401);
            }

            return $next($request);
        });
    }

    /**
     * Create a new tenant with Bagisto installation (WHMCS CreateAccount)
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'client_id' => 'required|integer',
            'domain' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'plan' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'password' => 'nullable|string|min:8',
            'storefront_type' => 'nullable|string|in:bagisto_default,nextjs,nuxt',
        ]);

        try {
            // Check if tenant already exists
            $existing = Tenant::where('whmcs_service_id', $validated['service_id'])->first();
            if ($existing) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Tenant already exists for this service',
                    'tenant_id' => $existing->id,
                    'admin_url' => $existing->getAdminUrl(),
                ], 409);
            }

            // Find the plan
            $plan = Plan::where('slug', $validated['plan'])
                ->orWhere('name', $validated['plan'])
                ->first();

            if (!$plan) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Plan not found: ' . $validated['plan'],
                ], 404);
            }

            // Create the tenant record
            $tenant = Tenant::create([
                'id' => Str::uuid()->toString(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'] ?? 'KE',
                'currency' => $this->getCurrencyForCountry($validated['country'] ?? 'KE'),
                'timezone' => $this->getTimezoneForCountry($validated['country'] ?? 'KE'),
                'locale' => 'en',
                'plan_id' => $plan->id,
                'subscription_status' => 'active',
                'is_active' => true,
                'whmcs_service_id' => $validated['service_id'],
                'whmcs_client_id' => $validated['client_id'],
                'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
                'ssl_status' => 'pending',
            ]);

            // Create the domain record
            $domain = $tenant->domains()->create([
                'domain' => $this->normalizeDomain($validated['domain']),
            ]);

            // Provision Bagisto installation (async via queue in production)
            $adminPassword = $validated['password'] ?? Str::random(16);

            $this->provisioner->provision($tenant, [
                'admin_email' => $validated['email'],
                'admin_password' => $adminPassword,
                'storefront_type' => $validated['storefront_type'] ?? 'bagisto_default',
            ]);

            Log::info('WHMCS: Created tenant with Bagisto', [
                'tenant_id' => $tenant->id,
                'service_id' => $validated['service_id'],
                'domain' => $domain->domain,
            ]);

            return response()->json([
                'result' => 'success',
                'message' => 'Store created successfully',
                'tenant_id' => $tenant->id,
                'domain' => $domain->domain,
                'admin_url' => $tenant->getAdminUrl(),
                'api_url' => $tenant->getApiUrl(),
                'storefront_url' => $tenant->getStorefrontUrl(),
            ]);

        } catch (\Exception $e) {
            Log::error('WHMCS: Failed to create tenant', [
                'error' => $e->getMessage(),
                'service_id' => $validated['service_id'],
            ]);

            return response()->json([
                'result' => 'error',
                'message' => 'Failed to create store: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a tenant's Bagisto store (WHMCS SuspendAccount)
     */
    public function suspend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $validated['service_id'])->first();

        if (!$tenant) {
            return response()->json([
                'result' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        // Suspend in database
        $tenant->suspend($validated['reason'] ?? 'Suspended via WHMCS');

        // Put Bagisto in maintenance mode
        $this->provisioner->suspend($tenant);

        Log::info('WHMCS: Suspended tenant', [
            'tenant_id' => $tenant->id,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Store suspended successfully',
        ]);
    }

    /**
     * Unsuspend a tenant's Bagisto store (WHMCS UnsuspendAccount)
     */
    public function unsuspend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $validated['service_id'])->first();

        if (!$tenant) {
            return response()->json([
                'result' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        // Unsuspend in database
        $tenant->unsuspend();

        // Bring Bagisto out of maintenance mode
        $this->provisioner->unsuspend($tenant);

        Log::info('WHMCS: Unsuspended tenant', [
            'tenant_id' => $tenant->id,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Store unsuspended successfully',
        ]);
    }

    /**
     * Terminate a tenant's Bagisto store (WHMCS TerminateAccount)
     */
    public function terminate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $validated['service_id'])->first();

        if (!$tenant) {
            return response()->json([
                'result' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        $tenantId = $tenant->id;

        // Delete Bagisto installation and database
        $this->provisioner->terminate($tenant);

        // Delete tenant record
        $tenant->domains()->delete();
        $tenant->delete();

        Log::info('WHMCS: Terminated tenant', [
            'tenant_id' => $tenantId,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Store terminated successfully',
        ]);
    }

    /**
     * Change tenant's plan (WHMCS ChangePackage)
     */
    public function changePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'plan' => 'required|string',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $validated['service_id'])->first();

        if (!$tenant) {
            return response()->json([
                'result' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        $plan = Plan::where('slug', $validated['plan'])
            ->orWhere('name', $validated['plan'])
            ->first();

        if (!$plan) {
            return response()->json([
                'result' => 'error',
                'message' => 'Plan not found: ' . $validated['plan'],
            ], 404);
        }

        $tenant->update(['plan_id' => $plan->id]);

        Log::info('WHMCS: Changed tenant plan', [
            'tenant_id' => $tenant->id,
            'service_id' => $validated['service_id'],
            'new_plan' => $plan->slug,
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Plan changed successfully',
        ]);
    }

    /**
     * Get tenant status (for WHMCS admin area)
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
        ]);

        $tenant = Tenant::with(['plan', 'domains'])
            ->where('whmcs_service_id', $validated['service_id'])
            ->first();

        if (!$tenant) {
            return response()->json([
                'result' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => $tenant->getPrimaryDomain(),
                'plan' => $tenant->plan?->name,
                'status' => $tenant->subscription_status,
                'is_active' => $tenant->is_active,
                'bagisto_installed' => $tenant->isBagistoInstalled(),
                'bagisto_version' => $tenant->bagisto_version,
                'storefront_type' => $tenant->storefront_type,
                'ssl_status' => $tenant->ssl_status,
                'admin_url' => $tenant->getAdminUrl(),
                'api_url' => $tenant->getApiUrl(),
                'storefront_url' => $tenant->getStorefrontUrl(),
                'created_at' => $tenant->created_at->toISOString(),
            ],
        ]);
    }

    private function getCurrencyForCountry(string $country): string
    {
        return match ($country) {
            'KE' => 'KES',
            'TZ' => 'TZS',
            'UG' => 'UGX',
            'RW' => 'RWF',
            'NG' => 'NGN',
            'GH' => 'GHS',
            'ZA' => 'ZAR',
            default => 'USD',
        };
    }

    private function getTimezoneForCountry(string $country): string
    {
        return match ($country) {
            'KE', 'TZ', 'UG' => 'Africa/Nairobi',
            'RW' => 'Africa/Kigali',
            'NG' => 'Africa/Lagos',
            'GH' => 'Africa/Accra',
            'ZA' => 'Africa/Johannesburg',
            default => 'UTC',
        };
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        return strtolower($domain);
    }
}
