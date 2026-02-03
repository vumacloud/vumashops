<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\CloudflareDnsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhmcsProvisioningController extends Controller
{
    protected CloudflareDnsService $cloudflare;

    public function __construct(CloudflareDnsService $cloudflare)
    {
        $this->cloudflare = $cloudflare;
    }

    /**
     * Create a new store/tenant account.
     *
     * WHMCS sends this on new order activation.
     *
     * Expected parameters:
     * - serviceid: WHMCS service ID (required)
     * - clientid: WHMCS client ID (required)
     * - domain: Custom domain for the store (required)
     * - email: Customer email (required)
     * - password: Customer password (optional, will generate if not provided)
     * - firstname: Customer first name (optional)
     * - lastname: Customer last name (optional)
     * - plan: Plan slug (optional, defaults to 'starter')
     * - configoptions: JSON encoded config options from WHMCS (optional)
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
            'clientid' => 'required|string',
            'domain' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string|min:8',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'plan' => 'nullable|string',
        ]);

        // Check if service already exists
        $existingTenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();
        if ($existingTenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service already provisioned',
                'tenant_id' => $existingTenant->id,
                'domain' => $existingTenant->domain,
            ], 409);
        }

        // Check if domain is already in use
        $domainInUse = Tenant::where('domain', $request->domain)->exists();
        if ($domainInUse) {
            return response()->json([
                'status' => 'error',
                'message' => 'Domain is already in use',
            ], 409);
        }

        // Find the plan
        $planSlug = $request->plan ?? 'starter';
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->first();

        if (!$plan) {
            // Fall back to first active plan
            $plan = Plan::where('is_active', true)->orderBy('sort_order')->first();
        }

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active plans available',
            ], 500);
        }

        try {
            DB::beginTransaction();

            // Generate store name from domain
            $storeName = $this->generateStoreName($request->domain, $request->firstname, $request->lastname);

            // Create the tenant
            $tenant = Tenant::create([
                'name' => $storeName,
                'email' => $request->email,
                'domain' => $request->domain,
                'plan_id' => $plan->id,
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addYear(), // WHMCS will manage actual expiry
                'is_active' => true,
                'currency' => 'USD',
                'timezone' => 'Africa/Nairobi',
                'locale' => 'en',
                'whmcs_service_id' => $request->serviceid,
                'whmcs_client_id' => $request->clientid,
                'metadata' => [
                    'provisioned_at' => now()->toISOString(),
                    'provisioned_by' => 'whmcs',
                ],
            ]);

            // Generate password if not provided
            $password = $request->password ?? Str::random(12);

            // Create the admin user for the tenant
            $adminName = trim(($request->firstname ?? '') . ' ' . ($request->lastname ?? ''));
            if (empty($adminName)) {
                $adminName = explode('@', $request->email)[0];
            }

            $admin = Admin::create([
                'tenant_id' => $tenant->id,
                'name' => $adminName,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Set up DNS for the domain via Cloudflare (if configured)
            $dnsResult = null;
            if (config('services.cloudflare.api_token')) {
                try {
                    $dnsResult = $this->cloudflare->addDomainRecord($request->domain);
                } catch (\Exception $e) {
                    Log::warning('Failed to set up Cloudflare DNS for tenant', [
                        'tenant_id' => $tenant->id,
                        'domain' => $request->domain,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail provisioning if DNS fails - can be set up manually
                }
            }

            DB::commit();

            Log::info('WHMCS: Tenant provisioned successfully', [
                'tenant_id' => $tenant->id,
                'whmcs_service_id' => $request->serviceid,
                'domain' => $request->domain,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Store created successfully',
                'tenant_id' => $tenant->id,
                'domain' => $tenant->domain,
                'admin_email' => $admin->email,
                'admin_password' => $password, // Return so WHMCS can send to customer
                'login_url' => 'https://' . $tenant->domain . '/admin',
                'dns_configured' => $dnsResult !== null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('WHMCS: Failed to provision tenant', [
                'whmcs_service_id' => $request->serviceid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create store: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a store/tenant account.
     *
     * WHMCS sends this when service is suspended (non-payment, abuse, etc).
     */
    public function suspend(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        if ($tenant->isSuspended()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Service already suspended',
            ]);
        }

        $tenant->suspend($request->reason ?? 'Suspended by WHMCS');

        Log::info('WHMCS: Tenant suspended', [
            'tenant_id' => $tenant->id,
            'whmcs_service_id' => $request->serviceid,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Store suspended successfully',
        ]);
    }

    /**
     * Unsuspend a store/tenant account.
     *
     * WHMCS sends this when service is unsuspended (payment received, etc).
     */
    public function unsuspend(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        if (!$tenant->isSuspended()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Service is not suspended',
            ]);
        }

        $tenant->unsuspend();

        Log::info('WHMCS: Tenant unsuspended', [
            'tenant_id' => $tenant->id,
            'whmcs_service_id' => $request->serviceid,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Store unsuspended successfully',
        ]);
    }

    /**
     * Terminate a store/tenant account.
     *
     * WHMCS sends this when service is terminated/cancelled.
     * This soft-deletes the tenant and all associated data.
     */
    public function terminate(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Deactivate all admins
            $tenant->admins()->update(['is_active' => false]);

            // Mark tenant as terminated
            $tenant->update([
                'subscription_status' => 'terminated',
                'is_active' => false,
                'metadata' => array_merge($tenant->metadata ?? [], [
                    'terminated_at' => now()->toISOString(),
                    'terminated_by' => 'whmcs',
                ]),
            ]);

            // Soft delete the tenant
            $tenant->delete();

            DB::commit();

            Log::info('WHMCS: Tenant terminated', [
                'tenant_id' => $tenant->id,
                'whmcs_service_id' => $request->serviceid,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Store terminated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('WHMCS: Failed to terminate tenant', [
                'whmcs_service_id' => $request->serviceid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to terminate store: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change the plan for a store/tenant.
     *
     * WHMCS sends this on upgrade/downgrade.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
            'plan' => 'required|string',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        $plan = Plan::where('slug', $request->plan)->where('is_active', true)->first();

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Plan not found: ' . $request->plan,
            ], 404);
        }

        $oldPlanId = $tenant->plan_id;
        $tenant->update(['plan_id' => $plan->id]);

        Log::info('WHMCS: Tenant plan changed', [
            'tenant_id' => $tenant->id,
            'whmcs_service_id' => $request->serviceid,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $plan->id,
            'new_plan_slug' => $plan->slug,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan changed successfully',
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
        ]);
    }

    /**
     * Renew/extend subscription for a store/tenant.
     *
     * WHMCS sends this when subscription is renewed.
     */
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
            'expires_at' => 'nullable|date',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        $expiresAt = $request->expires_at
            ? \Carbon\Carbon::parse($request->expires_at)
            : now()->addYear();

        $tenant->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $expiresAt,
            'is_active' => true,
        ]);

        // Unsuspend if was suspended
        if ($tenant->suspended_at) {
            $tenant->update([
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }

        Log::info('WHMCS: Tenant subscription renewed', [
            'tenant_id' => $tenant->id,
            'whmcs_service_id' => $request->serviceid,
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription renewed successfully',
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    /**
     * Get store/tenant status.
     *
     * Useful for WHMCS to check current status.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
        ]);

        $tenant = Tenant::with('plan')->where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'email' => $tenant->email,
                'subscription_status' => $tenant->subscription_status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toISOString(),
                'is_active' => $tenant->is_active,
                'is_suspended' => $tenant->isSuspended(),
                'plan' => $tenant->plan ? [
                    'id' => $tenant->plan->id,
                    'name' => $tenant->plan->name,
                    'slug' => $tenant->plan->slug,
                ] : null,
                'created_at' => $tenant->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update store/tenant details.
     *
     * WHMCS sends this when customer updates their domain or details.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'serviceid' => 'required|string',
            'domain' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $tenant = Tenant::where('whmcs_service_id', $request->serviceid)->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        }

        $updates = [];

        // Update domain if provided and different
        if ($request->has('domain') && $request->domain !== $tenant->domain) {
            // Check if new domain is already in use
            $domainInUse = Tenant::where('domain', $request->domain)
                ->where('id', '!=', $tenant->id)
                ->exists();

            if ($domainInUse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Domain is already in use',
                ], 409);
            }

            $updates['domain'] = $request->domain;

            // Update DNS if Cloudflare is configured
            if (config('services.cloudflare.api_token')) {
                try {
                    // Remove old domain record
                    $this->cloudflare->removeDomainRecord($tenant->domain);
                    // Add new domain record
                    $this->cloudflare->addDomainRecord($request->domain);
                } catch (\Exception $e) {
                    Log::warning('Failed to update Cloudflare DNS for domain change', [
                        'tenant_id' => $tenant->id,
                        'old_domain' => $tenant->domain,
                        'new_domain' => $request->domain,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Update email if provided
        if ($request->has('email') && $request->email !== $tenant->email) {
            $updates['email'] = $request->email;

            // Also update the owner admin's email
            $tenant->admins()->where('role', 'owner')->update(['email' => $request->email]);
        }

        if (!empty($updates)) {
            $tenant->update($updates);

            Log::info('WHMCS: Tenant updated', [
                'tenant_id' => $tenant->id,
                'whmcs_service_id' => $request->serviceid,
                'updates' => array_keys($updates),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Store updated successfully',
        ]);
    }

    /**
     * Generate a store name from domain or customer name.
     */
    protected function generateStoreName(string $domain, ?string $firstName, ?string $lastName): string
    {
        // If we have customer name, use it
        if ($firstName || $lastName) {
            $name = trim("$firstName $lastName");
            if (!empty($name)) {
                return $name . "'s Store";
            }
        }

        // Otherwise generate from domain
        $domainParts = explode('.', $domain);
        $name = $domainParts[0];

        // Clean up common prefixes
        $name = preg_replace('/^(www|shop|store|my)[-_]?/i', '', $name);

        // Convert to title case
        $name = Str::title(str_replace(['-', '_'], ' ', $name));

        return $name ?: 'My Store';
    }
}
