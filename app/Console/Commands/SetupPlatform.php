<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Console\Command;
class SetupPlatform extends Command
{
    protected $signature = 'vumashops:setup {--fresh : Drop all data first}';
    protected $description = 'Setup VumaShops platform with initial data';

    public function handle(): int
    {
        $this->info('Setting up VumaShops platform...');

        if ($this->option('fresh')) {
            $this->warn('Clearing existing data...');
            Admin::query()->delete();
            Tenant::query()->delete();
            Plan::query()->delete();
        }

        // 1. Create Plans
        $this->createPlans();

        // 2. Create Super Admin (no tenant)
        $this->createSuperAdmin();

        // 3. Create Demo Tenant and Admin
        $this->createDemoShop();

        $this->newLine();
        $this->info('✓ VumaShops setup complete!');
        $this->newLine();

        $this->table(['Item', 'Value'], [
            ['Platform URL', 'https://shops.vumacloud.com'],
            ['Super Admin', 'https://shops.vumacloud.com/super-admin'],
            ['Super Admin Email', 'admin@vumacloud.com'],
            ['Super Admin Password', 'password'],
            ['Demo Shop URL', 'https://demoshop.vumacloud.com'],
            ['Demo Admin Email', 'demo@vumacloud.com'],
            ['Demo Admin Password', 'demo123'],
        ]);

        $this->warn('⚠ Change these passwords in production!');

        return Command::SUCCESS;
    }

    protected function createPlans(): void
    {
        $this->info('Creating plans...');

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses just getting started',
                'limits' => ['products' => 50, 'categories' => 10, 'staff' => 2],
                'is_active' => true,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing businesses with more products',
                'limits' => ['products' => 500, 'categories' => 50, 'staff' => 5],
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For established businesses',
                'limits' => ['products' => 5000, 'categories' => 200, 'staff' => 15],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->info('  ✓ Created ' . count($plans) . ' plans');
    }

    protected function createSuperAdmin(): void
    {
        $this->info('Creating super admin...');

        Admin::updateOrCreate(
            ['email' => 'admin@vumacloud.com', 'tenant_id' => null],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_super_admin' => true,
            ]
        );

        $this->info('  ✓ Super admin created');
    }

    protected function createDemoShop(): void
    {
        $this->info('Creating demo shop...');

        $plan = Plan::where('slug', 'professional')->first();

        $tenant = Tenant::updateOrCreate(
            ['domain' => 'demoshop.vumacloud.com'],
            [
                'name' => 'VumaShops Demo Store',
                'slug' => 'demo-shop',
                'email' => 'demo@vumacloud.com',
                'phone' => '+254700000000',
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
                    'store_description' => 'Experience the power of VumaShops',
                    'whatsapp' => '+254700000000',
                ],
            ]
        );

        // Create demo store admin
        Admin::updateOrCreate(
            ['email' => 'demo@vumacloud.com', 'tenant_id' => $tenant->id],
            [
                'name' => 'Demo Store Admin',
                'password' => 'demo123',
                'is_super_admin' => false,
            ]
        );

        $this->info('  ✓ Demo shop created at demoshop.vumacloud.com');
    }
}
