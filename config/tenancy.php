<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */

    'tenant_model' => \App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | These domains will not be treated as tenant domains, they belong to
    | the central application (super admin panel).
    |
    */

    'central_domains' => array_filter(array_map('trim', explode(',', env('TENANCY_CENTRAL_DOMAINS', 'admin.vumashops.com,vumashops.com')))),

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | How tenants are identified - by domain, subdomain, or path.
    |
    */

    'identification_strategy' => 'domain', // 'domain', 'subdomain', or 'path'

    /*
    |--------------------------------------------------------------------------
    | Database Mode
    |--------------------------------------------------------------------------
    |
    | 'single' - All tenants share one database with tenant_id column
    | 'multi'  - Each tenant has their own database
    |
    */

    'database_mode' => 'single',

    /*
    |--------------------------------------------------------------------------
    | Tenant-Aware Models
    |--------------------------------------------------------------------------
    |
    | Models that should automatically scope queries to the current tenant.
    |
    */

    'tenant_aware_models' => [
        \App\Models\Product::class,
        \App\Models\Category::class,
        \App\Models\Order::class,
        \App\Models\Customer::class,
        \App\Models\Attribute::class,
        \App\Models\AttributeFamily::class,
        \App\Models\Cart::class,
        \App\Models\Wishlist::class,
        \App\Models\Review::class,
        \App\Models\Coupon::class,
        \App\Models\TaxRate::class,
        \App\Models\ShippingMethod::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features Available Per Plan
    |--------------------------------------------------------------------------
    */

    'plan_features' => [
        'starter' => [
            'products' => 50,
            'categories' => 10,
            'attributes' => 20,
            'orders' => 100,
            'staff_accounts' => 2,
            'custom_domain' => false,
            'analytics' => 'basic',
            'support' => 'email',
        ],
        'growth' => [
            'products' => 500,
            'categories' => 50,
            'attributes' => 100,
            'orders' => 1000,
            'staff_accounts' => 5,
            'custom_domain' => true,
            'analytics' => 'advanced',
            'support' => 'priority',
        ],
        'professional' => [
            'products' => 5000,
            'categories' => 200,
            'attributes' => 500,
            'orders' => 10000,
            'staff_accounts' => 15,
            'custom_domain' => true,
            'analytics' => 'advanced',
            'support' => 'priority',
        ],
        'enterprise' => [
            'products' => -1, // unlimited
            'categories' => -1,
            'attributes' => -1,
            'orders' => -1,
            'staff_accounts' => -1,
            'custom_domain' => true,
            'analytics' => 'enterprise',
            'support' => 'dedicated',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Settings
    |--------------------------------------------------------------------------
    */

    'default_settings' => [
        'store_name' => 'My Store',
        'store_description' => 'Welcome to my store',
        'currency' => 'KES',
        'timezone' => 'Africa/Nairobi',
        'locale' => 'en',
        'tax_enabled' => true,
        'tax_rate' => 16,
        'free_shipping_threshold' => 5000,
        'low_stock_threshold' => 5,
        'order_prefix' => 'ORD',
        'invoice_prefix' => 'INV',
    ],

];
