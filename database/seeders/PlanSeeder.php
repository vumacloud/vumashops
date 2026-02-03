<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * VumaShops by VumaCloud
     * Simple yearly pricing for African merchants
     * ALL plans include: domain + 3 email addresses + SSL
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses and WhatsApp sellers getting started online.',
                'monthly_price' => null, // Yearly only
                'yearly_price' => 59,
                'currency' => 'USD',
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'limits' => [
                    'products' => 50,
                    'categories' => 10,
                    'orders_per_month' => 100,
                    'staff_accounts' => 2,
                    'storage_mb' => 500,
                    'email_accounts' => 3, // 3 email addresses included
                    'custom_domain' => true,
                    'domain_included' => true, // .com or country TLD included
                    'ssl_certificate' => true,
                    'api_access' => false,
                    'themes' => ['starter', 'minimal', 'whatsapp'],
                ],
                'features' => [
                    'Free domain (.com, .co.ke, .co.ug, etc.)',
                    '3 professional email addresses',
                    'Free SSL certificate',
                    '50 products',
                    'M-Pesa & mobile money',
                    'WhatsApp order button',
                    'SMS & email notifications',
                    'Mobile-friendly store',
                    'Basic analytics',
                    'Email support',
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing businesses ready to scale their online presence.',
                'monthly_price' => null, // Yearly only
                'yearly_price' => 89,
                'currency' => 'USD',
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => true, // Most popular
                'sort_order' => 2,
                'limits' => [
                    'products' => 500,
                    'categories' => 50,
                    'orders_per_month' => 1000,
                    'staff_accounts' => 5,
                    'storage_mb' => 2000,
                    'email_accounts' => 3,
                    'custom_domain' => true,
                    'domain_included' => true,
                    'ssl_certificate' => true,
                    'api_access' => true,
                    'themes' => ['starter', 'minimal', 'whatsapp', 'modern', 'boutique', 'electronics', 'grocery'],
                ],
                'features' => [
                    'Everything in Starter, plus:',
                    '500 products',
                    'All premium themes',
                    'Discount codes & coupons',
                    'Abandoned cart recovery',
                    'All payment gateways',
                    'Advanced analytics',
                    'API access',
                    'Priority support',
                    'Cloudflare CDN',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'professional',
                'description' => 'For established businesses with high volume sales.',
                'monthly_price' => null, // Yearly only
                'yearly_price' => 129,
                'currency' => 'USD',
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'limits' => [
                    'products' => -1, // Unlimited
                    'categories' => -1,
                    'orders_per_month' => -1,
                    'staff_accounts' => 15,
                    'storage_mb' => 10000,
                    'email_accounts' => 3,
                    'custom_domain' => true,
                    'domain_included' => true,
                    'ssl_certificate' => true,
                    'api_access' => true,
                    'multi_currency' => true,
                    'themes' => 'all',
                    'custom_theme' => true,
                ],
                'features' => [
                    'Everything in Growth, plus:',
                    'Unlimited products',
                    'Unlimited orders',
                    'All themes + custom CSS',
                    'Multi-currency support',
                    'Advanced reporting',
                    'Bulk product import/export',
                    'Dedicated account manager',
                    'Phone support',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        // Disable Enterprise plan (not needed for this pricing model)
        Plan::where('slug', 'enterprise')->update(['is_active' => false]);
    }
}
