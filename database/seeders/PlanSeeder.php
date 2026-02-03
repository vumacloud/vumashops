<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * VumaShops by VumaCloud - All plans have 7-day free trial
     * ALL vendors MUST use custom domains (no vumacloud.com subdomains)
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses just getting started with e-commerce.',
                'monthly_price' => 29,
                'yearly_price' => 290,
                'currency' => 'USD',
                'trial_days' => 7, // 7-day free trial
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'limits' => [
                    'products' => 50,
                    'categories' => 10,
                    'attributes' => 20,
                    'attribute_families' => 5,
                    'orders' => 100,
                    'staff_accounts' => 2,
                    'storage_mb' => 500,
                    'custom_domain' => true, // REQUIRED - all vendors use custom domains
                    'api_access' => false,
                    'priority_support' => false,
                    'analytics' => 'basic',
                    'themes' => ['starter', 'minimal'], // Available themes
                ],
                'features' => [
                    'Custom domain (required)',
                    'Basic storefront themes',
                    'Mobile responsive design',
                    'M-Pesa integration',
                    'Email notifications (Brevo)',
                    'SMS notifications (Africa\'s Talking)',
                    'Basic analytics',
                    'Email support',
                    'SSL certificate included',
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing businesses that need more features and capacity.',
                'monthly_price' => 79,
                'yearly_price' => 790,
                'currency' => 'USD',
                'trial_days' => 7, // 7-day free trial
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'limits' => [
                    'products' => 500,
                    'categories' => 50,
                    'attributes' => 100,
                    'attribute_families' => 20,
                    'orders' => 1000,
                    'staff_accounts' => 5,
                    'storage_mb' => 2000,
                    'custom_domain' => true, // REQUIRED
                    'api_access' => true,
                    'priority_support' => true,
                    'analytics' => 'advanced',
                    'themes' => ['starter', 'minimal', 'modern', 'boutique', 'electronics'],
                ],
                'features' => [
                    'Everything in Starter',
                    'All premium themes',
                    'All payment gateways',
                    'Advanced analytics',
                    'API access',
                    'Priority support',
                    'Discount codes',
                    'Abandoned cart recovery',
                    'Cloudflare CDN',
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For established businesses with high volume sales.',
                'monthly_price' => 199,
                'yearly_price' => 1990,
                'currency' => 'USD',
                'trial_days' => 7, // 7-day free trial
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'limits' => [
                    'products' => 5000,
                    'categories' => 200,
                    'attributes' => 500,
                    'attribute_families' => 50,
                    'orders' => 10000,
                    'staff_accounts' => 15,
                    'storage_mb' => 10000,
                    'custom_domain' => true, // REQUIRED
                    'api_access' => true,
                    'priority_support' => true,
                    'analytics' => 'advanced',
                    'multi_currency' => true,
                    'multi_language' => true,
                    'themes' => 'all', // All themes including custom
                ],
                'features' => [
                    'Everything in Growth',
                    'All themes + custom themes',
                    'Multi-currency support',
                    'Multi-language support',
                    'Advanced reporting',
                    'Bulk operations',
                    'Custom integrations',
                    'Dedicated account manager',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solutions for large enterprises with unlimited needs.',
                'monthly_price' => 499,
                'yearly_price' => 4990,
                'currency' => 'USD',
                'trial_days' => 7, // 7-day free trial
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'limits' => [
                    'products' => -1, // unlimited
                    'categories' => -1,
                    'attributes' => -1,
                    'attribute_families' => -1,
                    'orders' => -1,
                    'staff_accounts' => -1,
                    'storage_mb' => -1,
                    'custom_domain' => true, // REQUIRED
                    'api_access' => true,
                    'priority_support' => true,
                    'analytics' => 'enterprise',
                    'multi_currency' => true,
                    'multi_language' => true,
                    'white_label' => true,
                    'themes' => 'all',
                    'custom_theme_development' => true,
                ],
                'features' => [
                    'Everything in Professional',
                    'Unlimited everything',
                    'Custom theme development',
                    'White-label option',
                    'Custom development',
                    'SLA guarantee',
                    'Dedicated support team',
                    'On-premise deployment option',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
