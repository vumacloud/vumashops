<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupPlatform extends Command
{
    protected $signature = 'vumashops:setup {--fresh : Drop all data and start fresh}';
    protected $description = 'Setup VumaShops platform with initial data';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       VumaShops Platform Setup           ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('fresh')) {
            if (!$this->confirm('This will DELETE all existing data. Continue?', true)) {
                $this->info('Setup cancelled.');
                return Command::SUCCESS;
            }
            $this->clearAllData();
        }

        $this->createPlans();
        $this->createSuperAdmin();
        $this->createDemoShop();

        $this->info('');
        $this->info('✅ VumaShops setup complete!');
        $this->info('');

        $this->table(['Resource', 'URL/Value'], [
            ['Platform', 'https://shops.vumacloud.com'],
            ['Super Admin Panel', 'https://shops.vumacloud.com/super-admin'],
            ['Super Admin Email', 'admin@vumacloud.com'],
            ['Super Admin Password', 'password'],
            ['', ''],
            ['Demo Shop', 'https://demoshop.vumacloud.com'],
            ['Demo Admin Panel', 'https://demoshop.vumacloud.com/admin'],
            ['Demo Admin Email', 'demo@vumacloud.com'],
            ['Demo Admin Password', 'demo123'],
        ]);

        $this->warn('');
        $this->warn('⚠️  IMPORTANT: Change these default passwords in production!');
        $this->warn('');

        return Command::SUCCESS;
    }

    protected function clearAllData(): void
    {
        $this->warn('Clearing all existing data...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in correct order
        $tables = [
            'admins',
            'tenants',
            'plans',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ Cleared {$table}");
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('  All data cleared.');
    }

    protected function createPlans(): void
    {
        $this->info('Creating plans...');

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses just getting started',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency' => 'KES',
                'trial_days' => 14,
                'limits' => [
                    'products' => 50,
                    'categories' => 10,
                    'staff' => 2,
                    'storage_mb' => 500,
                ],
                'features' => ['basic_analytics', 'email_support'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing businesses with more products',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency' => 'KES',
                'trial_days' => 14,
                'limits' => [
                    'products' => 500,
                    'categories' => 50,
                    'staff' => 5,
                    'storage_mb' => 2000,
                ],
                'features' => ['advanced_analytics', 'priority_support', 'custom_domain'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For established businesses needing more power',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency' => 'KES',
                'trial_days' => 14,
                'limits' => [
                    'products' => 5000,
                    'categories' => 200,
                    'staff' => 15,
                    'storage_mb' => 10000,
                ],
                'features' => ['advanced_analytics', 'priority_support', 'custom_domain', 'api_access'],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }

        $this->info('  ✓ Created ' . count($plans) . ' plans');
    }

    protected function createSuperAdmin(): void
    {
        $this->info('Creating super admin...');

        // Check if super admin already exists
        $existing = Admin::where('email', 'admin@vumacloud.com')->first();
        if ($existing) {
            $this->line('  → Super admin already exists, skipping.');
            return;
        }

        Admin::create([
            'tenant_id' => null,
            'name' => 'Super Admin',
            'email' => 'admin@vumacloud.com',
            'password' => 'password', // Model auto-hashes via 'hashed' cast
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->info('  ✓ Super admin created');
    }

    protected function createDemoShop(): void
    {
        $this->info('Creating demo shop...');

        // Check if demo shop already exists
        $existing = Tenant::where('domain', 'demoshop.vumacloud.com')->first();
        if ($existing) {
            $this->line('  → Demo shop already exists, skipping.');
            return;
        }

        $plan = Plan::where('slug', 'professional')->first();

        $tenant = Tenant::create([
            'name' => 'VumaShops Demo Store',
            'slug' => 'demo-shop',
            'email' => 'demo@vumacloud.com',
            'phone' => '+254700000000',
            'domain' => 'demoshop.vumacloud.com',
            'plan_id' => $plan?->id,
            'country' => 'KE',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'theme' => 'starter',
            'is_active' => true,
            'domain_verified' => true,
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addYear(),
            'settings' => [
                'store_name' => 'VumaShops Demo',
                'store_description' => 'Experience the power of VumaShops - E-commerce for African businesses',
                'whatsapp' => '+254700000000',
                'show_prices' => true,
                'allow_guest_checkout' => true,
            ],
        ]);

        // Create demo store admin
        Admin::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Store Admin',
            'email' => 'demo@vumacloud.com',
            'password' => 'demo123', // Model auto-hashes
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->info('  ✓ Demo shop created at demoshop.vumacloud.com');
    }
}
