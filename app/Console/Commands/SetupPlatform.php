<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupPlatform extends Command
{
    protected $signature = 'vumashops:setup
                            {--fresh : Drop and recreate all tables}
                            {--seed : Seed default data}';

    protected $description = 'Set up the VumaShops central platform';

    public function handle(): int
    {
        $this->info('Setting up VumaShops Central Platform...');
        $this->newLine();

        // Run migrations
        if ($this->option('fresh')) {
            $this->warn('Dropping all tables and re-migrating...');
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->info('Running migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        // Seed default data
        if ($this->option('seed') || $this->option('fresh')) {
            $this->seedDefaultData();
        }

        // Create super admin if none exists
        if (SuperAdmin::count() === 0) {
            $this->createSuperAdmin();
        }

        $this->newLine();
        $this->info('VumaShops setup complete!');
        $this->newLine();

        $this->table(['Component', 'Status'], [
            ['Database', 'Migrated'],
            ['Plans', Plan::count() . ' plans'],
            ['Super Admins', SuperAdmin::count() . ' admins'],
        ]);

        $this->newLine();
        $this->info('Access the admin panel at: /admin');

        return Command::SUCCESS;
    }

    protected function seedDefaultData(): void
    {
        $this->info('Seeding default plans...');

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses getting started',
                'price_monthly' => 2999,
                'price_yearly' => 29990,
                'trial_days' => 14,
                'limits' => [
                    'max_products' => 100,
                    'max_orders' => 500,
                    'max_staff' => 2,
                    'storage_gb' => 5,
                    'custom_domain' => true,
                    'ssl_certificate' => true,
                    'analytics' => false,
                    'api_access' => false,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For growing businesses with more needs',
                'price_monthly' => 7999,
                'price_yearly' => 79990,
                'trial_days' => 14,
                'limits' => [
                    'max_products' => 1000,
                    'max_orders' => 5000,
                    'max_staff' => 10,
                    'storage_gb' => 25,
                    'custom_domain' => true,
                    'ssl_certificate' => true,
                    'analytics' => true,
                    'api_access' => true,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited everything for large businesses',
                'price_monthly' => 19999,
                'price_yearly' => 199990,
                'trial_days' => 14,
                'limits' => [
                    'max_products' => 0, // unlimited
                    'max_orders' => 0,   // unlimited
                    'max_staff' => 0,    // unlimited
                    'storage_gb' => 100,
                    'custom_domain' => true,
                    'ssl_certificate' => true,
                    'analytics' => true,
                    'api_access' => true,
                    'priority_support' => true,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->info('Created ' . count($plans) . ' plans');
    }

    protected function createSuperAdmin(): void
    {
        $this->info('Creating super admin...');
        $this->newLine();

        $name = $this->ask('Admin name', 'Super Admin');
        $email = $this->ask('Admin email', 'admin@vumacloud.com');
        $password = $this->secret('Admin password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters');
            return;
        }

        SuperAdmin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info("Super admin created: {$email}");
    }
}
