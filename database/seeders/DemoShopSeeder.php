<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo Shop Seeder
 *
 * Creates the VumaShops demo store at demoshop.vumacloud.com
 * This showcases the platform's capabilities to potential customers.
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating VumaShops Demo Store...');

        // Get the Enterprise plan for demo
        $plan = Plan::where('slug', 'enterprise')->first();

        if (!$plan) {
            $this->command->error('Please run PlanSeeder first!');
            return;
        }

        // Create the demo tenant
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'demoshop'],
            [
                'name' => 'VumaShops Demo Store',
                'slug' => 'demoshop',
                'email' => 'demo@vumacloud.com',
                'domain' => 'demoshop.vumacloud.com', // Special case: demo uses vumacloud.com subdomain
                'domain_verified' => true,
                'country' => 'KE',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'locale' => 'en',
                'theme' => 'whatsapp', // Using WhatsApp Commerce theme for demo
                'is_active' => true,
                'settings' => [
                    'store_name' => 'VumaShops Demo Store',
                    'store_tagline' => 'Experience the Power of African E-Commerce',
                    'store_description' => 'This is a demo store showcasing VumaShops - the multi-tenant e-commerce platform built for African businesses.',
                    'contact_email' => 'demo@vumacloud.com',
                    'contact_phone' => '+254700000000',
                    'whatsapp_number' => '+254700000000',
                    'address' => 'Nairobi, Kenya',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/vumashops',
                        'twitter' => 'https://twitter.com/vumashops',
                        'instagram' => 'https://instagram.com/vumashops',
                    ],
                    'payment_methods' => ['mpesa_kenya', 'paystack', 'flutterwave'],
                    'shipping_methods' => ['standard', 'express', 'pickup'],
                    'seo' => [
                        'meta_title' => 'VumaShops Demo - African E-Commerce Platform',
                        'meta_description' => 'Experience VumaShops, the multi-tenant e-commerce platform for African businesses with M-Pesa, Paystack, and more.',
                    ],
                ],
                'metadata' => [
                    'is_demo' => true,
                    'created_by' => 'system',
                ],
            ]
        );

        // Create demo subscription (never expires)
        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'price' => 0, // Free for demo
                'currency' => 'USD',
                'billing_cycle' => 'yearly',
                'trial_ends_at' => null,
                'starts_at' => now(),
                'ends_at' => now()->addYears(100), // Effectively never expires
                'auto_renew' => false,
            ]
        );

        // Create demo admin
        Admin::updateOrCreate(
            ['email' => 'demo@vumacloud.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demo Admin',
                'email' => 'demo@vumacloud.com',
                'password' => Hash::make('demo123'),
                'phone' => '+254700000000',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create demo categories
        $categories = $this->createDemoCategories($tenant);

        // Create demo products
        $this->createDemoProducts($tenant, $categories);

        // Create demo customers
        $customers = $this->createDemoCustomers($tenant);

        // Create demo orders
        $this->createDemoOrders($tenant, $customers);

        $this->command->info('Demo store created successfully!');
        $this->command->info('URL: https://demoshop.vumacloud.com');
        $this->command->info('Admin: demo@vumacloud.com / demo123');
    }

    protected function createDemoCategories(Tenant $tenant): array
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Latest electronics and gadgets',
                'children' => [
                    ['name' => 'Phones & Tablets', 'slug' => 'phones-tablets'],
                    ['name' => 'Laptops', 'slug' => 'laptops'],
                    ['name' => 'Accessories', 'slug' => 'electronics-accessories'],
                ],
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Trendy African fashion',
                'children' => [
                    ['name' => 'Men\'s Clothing', 'slug' => 'mens-clothing'],
                    ['name' => 'Women\'s Clothing', 'slug' => 'womens-clothing'],
                    ['name' => 'Shoes', 'slug' => 'shoes'],
                ],
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-living',
                'description' => 'Home decor and essentials',
                'children' => [
                    ['name' => 'Furniture', 'slug' => 'furniture'],
                    ['name' => 'Kitchen', 'slug' => 'kitchen'],
                    ['name' => 'Decor', 'slug' => 'decor'],
                ],
            ],
            [
                'name' => 'African Crafts',
                'slug' => 'african-crafts',
                'description' => 'Handmade African artisan products',
                'children' => [
                    ['name' => 'Jewelry', 'slug' => 'jewelry'],
                    ['name' => 'Art & Paintings', 'slug' => 'art-paintings'],
                    ['name' => 'Textiles', 'slug' => 'textiles'],
                ],
            ],
        ];

        $createdCategories = [];

        foreach ($categories as $catData) {
            $parent = Category::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'is_active' => true,
                    'position' => 0,
                ]
            );

            $createdCategories[$catData['slug']] = $parent;

            foreach ($catData['children'] ?? [] as $childData) {
                $child = Category::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $childData['slug']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childData['name'],
                        'is_active' => true,
                        'position' => 0,
                    ]
                );
                $createdCategories[$childData['slug']] = $child;
            }
        }

        return $createdCategories;
    }

    protected function createDemoProducts(Tenant $tenant, array $categories): void
    {
        $products = [
            // Electronics
            [
                'name' => 'Samsung Galaxy A54 5G',
                'sku' => 'DEMO-PHONE-001',
                'price' => 45000,
                'description' => 'Awesome 5G smartphone with great camera and long battery life.',
                'category' => 'phones-tablets',
                'stock' => 50,
            ],
            [
                'name' => 'HP Pavilion Laptop 15',
                'sku' => 'DEMO-LAPTOP-001',
                'price' => 85000,
                'description' => 'Powerful laptop for work and entertainment.',
                'category' => 'laptops',
                'stock' => 25,
            ],
            [
                'name' => 'Wireless Earbuds Pro',
                'sku' => 'DEMO-ACC-001',
                'price' => 3500,
                'description' => 'High-quality wireless earbuds with noise cancellation.',
                'category' => 'electronics-accessories',
                'stock' => 100,
            ],
            // Fashion
            [
                'name' => 'African Print Ankara Shirt',
                'sku' => 'DEMO-MENS-001',
                'price' => 2500,
                'description' => 'Beautiful African print shirt, 100% cotton.',
                'category' => 'mens-clothing',
                'stock' => 75,
            ],
            [
                'name' => 'Kitenge Maxi Dress',
                'sku' => 'DEMO-WOMENS-001',
                'price' => 4500,
                'description' => 'Elegant Kitenge maxi dress, perfect for any occasion.',
                'category' => 'womens-clothing',
                'stock' => 40,
            ],
            [
                'name' => 'Leather Sandals - Maasai Style',
                'sku' => 'DEMO-SHOES-001',
                'price' => 1800,
                'description' => 'Handcrafted leather sandals with traditional Maasai beadwork.',
                'category' => 'shoes',
                'stock' => 60,
            ],
            // African Crafts
            [
                'name' => 'Maasai Beaded Necklace',
                'sku' => 'DEMO-JEWELRY-001',
                'price' => 1500,
                'description' => 'Authentic Maasai beaded necklace, handmade by local artisans.',
                'category' => 'jewelry',
                'stock' => 30,
            ],
            [
                'name' => 'African Wildlife Painting',
                'sku' => 'DEMO-ART-001',
                'price' => 12000,
                'description' => 'Original oil painting featuring African wildlife on canvas.',
                'category' => 'art-paintings',
                'stock' => 10,
            ],
            [
                'name' => 'Kente Cloth - Premium',
                'sku' => 'DEMO-TEXTILE-001',
                'price' => 8500,
                'description' => 'Authentic Ghanaian Kente cloth, perfect for special occasions.',
                'category' => 'textiles',
                'stock' => 20,
            ],
            // Home
            [
                'name' => 'Wooden Coffee Table - African Design',
                'sku' => 'DEMO-FURN-001',
                'price' => 15000,
                'description' => 'Handcrafted wooden coffee table with African motifs.',
                'category' => 'furniture',
                'stock' => 15,
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories[$productData['category']] ?? null;

            $product = Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $productData['sku']],
                [
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'type' => 'simple',
                    'description' => $productData['description'],
                    'short_description' => Str::limit($productData['description'], 100),
                    'price' => $productData['price'],
                    'cost_price' => $productData['price'] * 0.6,
                    'compare_at_price' => $productData['price'] * 1.2,
                    'currency' => 'KES',
                    'stock_quantity' => $productData['stock'],
                    'low_stock_threshold' => 10,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_featured' => rand(0, 1) === 1,
                    'is_taxable' => true,
                    'tax_class' => 'standard',
                    'weight' => rand(100, 5000),
                    'weight_unit' => 'g',
                ]
            );

            // Attach category
            if ($category) {
                $product->categories()->sync([$category->id]);
            }
        }
    }

    protected function createDemoCustomers(Tenant $tenant): array
    {
        $customers = [
            [
                'name' => 'John Kamau',
                'email' => 'john.kamau@example.com',
                'phone' => '+254711111111',
            ],
            [
                'name' => 'Mary Wanjiku',
                'email' => 'mary.wanjiku@example.com',
                'phone' => '+254722222222',
            ],
            [
                'name' => 'David Ochieng',
                'email' => 'david.ochieng@example.com',
                'phone' => '+254733333333',
            ],
        ];

        $createdCustomers = [];

        foreach ($customers as $customerData) {
            $customer = Customer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $customerData['email']],
                [
                    'name' => $customerData['name'],
                    'phone' => $customerData['phone'],
                    'password' => Hash::make('customer123'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $createdCustomers[] = $customer;
        }

        return $createdCustomers;
    }

    protected function createDemoOrders(Tenant $tenant, array $customers): void
    {
        $products = Product::where('tenant_id', $tenant->id)->get();
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed'];

        foreach ($customers as $customer) {
            // Create 2-3 orders per customer
            $numOrders = rand(2, 3);

            for ($i = 0; $i < $numOrders; $i++) {
                $orderProducts = $products->random(rand(1, 3));
                $subtotal = 0;
                $items = [];

                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $itemTotal = $product->price * $quantity;
                    $subtotal += $itemTotal;

                    $items[] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'type' => 'simple',
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'total' => $itemTotal,
                    ];
                }

                $shippingAmount = 300;
                $grandTotal = $subtotal + $shippingAmount;

                $order = Order::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'order_number' => 'DEMO-' . strtoupper(Str::random(8)),
                    'status' => $statuses[array_rand($statuses)],
                    'payment_status' => rand(0, 1) ? 'paid' : 'pending',
                    'payment_method' => ['mpesa_kenya', 'paystack'][rand(0, 1)],
                    'currency' => 'KES',
                    'subtotal' => $subtotal,
                    'shipping_amount' => $shippingAmount,
                    'grand_total' => $grandTotal,
                    'total_items' => count($items),
                    'total_quantity' => collect($items)->sum('quantity'),
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'customer_name' => $customer->name,
                    'billing_address' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'address' => 'Nairobi, Kenya',
                        'city' => 'Nairobi',
                        'country' => 'KE',
                    ],
                    'shipping_address' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'address' => 'Nairobi, Kenya',
                        'city' => 'Nairobi',
                        'country' => 'KE',
                    ],
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                // Create order items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        ...$item,
                    ]);
                }
            }
        }
    }
}
