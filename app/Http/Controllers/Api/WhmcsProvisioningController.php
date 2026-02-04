<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class WhmcsProvisioningController extends Controller
{
    /**
     * Verify WHMCS API key middleware
     */
    public function __construct()
    {
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
     * Create a new tenant (WHMCS module CreateAccount)
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
        ]);

        try {
            // Check if tenant already exists for this service
            $existing = Tenant::where('whmcs_service_id', $validated['service_id'])->first();
            if ($existing) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Tenant already exists for this service',
                    'tenant_id' => $existing->id,
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

            // Create the tenant
            $tenant = Tenant::create([
                'id' => Str::uuid()->toString(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'] ?? 'KE',
                'currency' => $this->getCurrencyForCountry($validated['country'] ?? 'KE'),
                'plan_id' => $plan->id,
                'subscription_status' => 'active',
                'is_active' => true,
                'whmcs_service_id' => $validated['service_id'],
                'whmcs_client_id' => $validated['client_id'],
                'trial_ends_at' => now()->addDays($plan->trial_days ?? 0),
            ]);

            // Create the domain
            $domain = $tenant->domains()->create([
                'domain' => $this->normalizeDomain($validated['domain']),
            ]);

            // Run tenant migrations to create the database
            $tenant->run(function () use ($validated) {
                // Create the initial admin user in tenant database
                \App\Models\User::create([
                    'name' => 'Store Admin',
                    'email' => $validated['email'],
                    'password' => bcrypt($validated['password'] ?? Str::random(16)),
                    'role' => 'admin',
                    'is_active' => true,
                ]);
            });

            Log::info('WHMCS: Created tenant', [
                'tenant_id' => $tenant->id,
                'service_id' => $validated['service_id'],
                'domain' => $domain->domain,
            ]);

            return response()->json([
                'result' => 'success',
                'message' => 'Tenant created successfully',
                'tenant_id' => $tenant->id,
                'domain' => $domain->domain,
            ]);

        } catch (\Exception $e) {
            Log::error('WHMCS: Failed to create tenant', [
                'error' => $e->getMessage(),
                'service_id' => $validated['service_id'],
            ]);

            return response()->json([
                'result' => 'error',
                'message' => 'Failed to create tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a tenant (WHMCS module SuspendAccount)
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

        $tenant->suspend($validated['reason'] ?? 'Suspended via WHMCS');

        Log::info('WHMCS: Suspended tenant', [
            'tenant_id' => $tenant->id,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Tenant suspended successfully',
        ]);
    }

    /**
     * Unsuspend a tenant (WHMCS module UnsuspendAccount)
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

        $tenant->unsuspend();

        Log::info('WHMCS: Unsuspended tenant', [
            'tenant_id' => $tenant->id,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Tenant unsuspended successfully',
        ]);
    }

    /**
     * Terminate a tenant (WHMCS module TerminateAccount)
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

        // Delete the tenant (this will also drop the database via stancl/tenancy)
        $tenant->delete();

        Log::info('WHMCS: Terminated tenant', [
            'tenant_id' => $tenantId,
            'service_id' => $validated['service_id'],
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Tenant terminated successfully',
        ]);
    }

    /**
     * Change tenant's plan (WHMCS module ChangePackage)
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

        $tenant->update([
            'plan_id' => $plan->id,
        ]);

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
                'ssl_status' => $tenant->ssl_status,
                'created_at' => $tenant->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get currency for a country
     */
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

    /**
     * Normalize domain (remove protocol, trailing slash)
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        return strtolower($domain);
    }
}
