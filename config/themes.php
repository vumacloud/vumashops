<?php

/**
 * Store Theme Configuration
 *
 * VumaShops by VumaCloud
 * Themes available for merchants to customize their storefronts.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    */

    'default' => 'starter',

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    */

    'themes' => [

        'starter' => [
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Clean and simple theme perfect for getting started.',
            'preview_image' => '/images/themes/starter-preview.jpg',
            'is_free' => true,
            'plans' => ['starter', 'growth', 'professional', 'enterprise'],
            'category' => 'general',
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#10B981',
                'accent' => '#F59E0B',
                'background' => '#FFFFFF',
                'text' => '#1F2937',
            ],
            'features' => [
                'Responsive design',
                'Mobile-first layout',
                'Quick product view',
                'Sticky header',
                'Newsletter signup',
            ],
        ],

        'minimal' => [
            'name' => 'Minimal',
            'slug' => 'minimal',
            'description' => 'Minimalist design that puts your products first.',
            'preview_image' => '/images/themes/minimal-preview.jpg',
            'is_free' => true,
            'plans' => ['starter', 'growth', 'professional', 'enterprise'],
            'category' => 'general',
            'colors' => [
                'primary' => '#000000',
                'secondary' => '#6B7280',
                'accent' => '#EF4444',
                'background' => '#FFFFFF',
                'text' => '#111827',
            ],
            'features' => [
                'Ultra-clean layout',
                'Large product images',
                'Minimal navigation',
                'Focus on typography',
                'Fast loading',
            ],
        ],

        'modern' => [
            'name' => 'Modern',
            'slug' => 'modern',
            'description' => 'Contemporary design with bold visuals and animations.',
            'preview_image' => '/images/themes/modern-preview.jpg',
            'is_free' => false,
            'plans' => ['growth', 'professional', 'enterprise'],
            'category' => 'general',
            'colors' => [
                'primary' => '#7C3AED',
                'secondary' => '#EC4899',
                'accent' => '#06B6D4',
                'background' => '#F9FAFB',
                'text' => '#1F2937',
            ],
            'features' => [
                'Smooth animations',
                'Gradient backgrounds',
                'Video hero section',
                'Product hover effects',
                'Modern typography',
                'Mega menu navigation',
            ],
        ],

        'boutique' => [
            'name' => 'Boutique',
            'slug' => 'boutique',
            'description' => 'Elegant theme perfect for fashion, jewelry, and luxury goods.',
            'preview_image' => '/images/themes/boutique-preview.jpg',
            'is_free' => false,
            'plans' => ['growth', 'professional', 'enterprise'],
            'category' => 'fashion',
            'colors' => [
                'primary' => '#B8860B',
                'secondary' => '#1F1F1F',
                'accent' => '#C9A961',
                'background' => '#FFFAF5',
                'text' => '#2D2D2D',
            ],
            'features' => [
                'Lookbook gallery',
                'Size guide popup',
                'Product zoom',
                'Instagram feed',
                'Collection pages',
                'Sale countdown timer',
            ],
        ],

        'electronics' => [
            'name' => 'TechStore',
            'slug' => 'electronics',
            'description' => 'Perfect for electronics, gadgets, and tech products.',
            'preview_image' => '/images/themes/electronics-preview.jpg',
            'is_free' => false,
            'plans' => ['growth', 'professional', 'enterprise'],
            'category' => 'electronics',
            'colors' => [
                'primary' => '#2563EB',
                'secondary' => '#1E3A8A',
                'accent' => '#F97316',
                'background' => '#F1F5F9',
                'text' => '#0F172A',
            ],
            'features' => [
                'Spec comparison',
                'Feature highlights',
                'Review system',
                'Warranty badges',
                'Product videos',
                'Related products',
            ],
        ],

        'grocery' => [
            'name' => 'FreshMart',
            'slug' => 'grocery',
            'description' => 'Designed for grocery stores, supermarkets, and food delivery.',
            'preview_image' => '/images/themes/grocery-preview.jpg',
            'is_free' => false,
            'plans' => ['growth', 'professional', 'enterprise'],
            'category' => 'food',
            'colors' => [
                'primary' => '#16A34A',
                'secondary' => '#4ADE80',
                'accent' => '#FACC15',
                'background' => '#FFFFFF',
                'text' => '#166534',
            ],
            'features' => [
                'Quick add to cart',
                'Quantity selector',
                'Delivery time slots',
                'Category sidebar',
                'Search suggestions',
                'Weekly specials banner',
            ],
        ],

        'africa' => [
            'name' => 'AfroStyle',
            'slug' => 'africa',
            'description' => 'Vibrant African-inspired design celebrating African culture.',
            'preview_image' => '/images/themes/africa-preview.jpg',
            'is_free' => false,
            'plans' => ['growth', 'professional', 'enterprise'],
            'category' => 'cultural',
            'colors' => [
                'primary' => '#DC2626',
                'secondary' => '#15803D',
                'accent' => '#FBBF24',
                'background' => '#FFFBEB',
                'text' => '#1C1917',
            ],
            'features' => [
                'African patterns',
                'Bold color palette',
                'Storytelling sections',
                'Artisan spotlight',
                'Cultural heritage display',
                'Community features',
            ],
        ],

        'marketplace' => [
            'name' => 'Marketplace',
            'slug' => 'marketplace',
            'description' => 'Multi-category marketplace design for diverse product ranges.',
            'preview_image' => '/images/themes/marketplace-preview.jpg',
            'is_free' => false,
            'plans' => ['professional', 'enterprise'],
            'category' => 'marketplace',
            'colors' => [
                'primary' => '#EA580C',
                'secondary' => '#0891B2',
                'accent' => '#8B5CF6',
                'background' => '#FFFFFF',
                'text' => '#0F172A',
            ],
            'features' => [
                'Category mega menu',
                'Daily deals section',
                'Flash sales',
                'Seller badges',
                'Multi-vendor support',
                'Advanced filtering',
            ],
        ],

        'whatsapp' => [
            'name' => 'WhatsApp Commerce',
            'slug' => 'whatsapp',
            'description' => 'Optimized for WhatsApp-first businesses in Africa.',
            'preview_image' => '/images/themes/whatsapp-preview.jpg',
            'is_free' => true,
            'plans' => ['starter', 'growth', 'professional', 'enterprise'],
            'category' => 'social',
            'colors' => [
                'primary' => '#25D366',
                'secondary' => '#128C7E',
                'accent' => '#34B7F1',
                'background' => '#FFFFFF',
                'text' => '#1F2937',
            ],
            'features' => [
                'WhatsApp order button',
                'Share to WhatsApp',
                'WhatsApp catalog sync',
                'Quick inquiry button',
                'Mobile-optimized checkout',
                'Chat widget integration',
                'Order via WhatsApp flow',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Categories
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'general' => 'General Purpose',
        'fashion' => 'Fashion & Apparel',
        'electronics' => 'Electronics & Tech',
        'food' => 'Food & Grocery',
        'cultural' => 'Cultural & Artisan',
        'marketplace' => 'Marketplace',
        'social' => 'Social Commerce',
    ],

    /*
    |--------------------------------------------------------------------------
    | Customization Options
    |--------------------------------------------------------------------------
    */

    'customization' => [
        'colors' => true,
        'fonts' => true,
        'logo' => true,
        'favicon' => true,
        'hero_banner' => true,
        'footer_content' => true,
        'social_links' => true,
        'custom_css' => ['professional', 'enterprise'],
        'custom_js' => ['enterprise'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Font Options
    |--------------------------------------------------------------------------
    */

    'fonts' => [
        'inter' => ['name' => 'Inter', 'family' => 'Inter, sans-serif'],
        'poppins' => ['name' => 'Poppins', 'family' => 'Poppins, sans-serif'],
        'roboto' => ['name' => 'Roboto', 'family' => 'Roboto, sans-serif'],
        'open-sans' => ['name' => 'Open Sans', 'family' => 'Open Sans, sans-serif'],
        'lato' => ['name' => 'Lato', 'family' => 'Lato, sans-serif'],
        'playfair' => ['name' => 'Playfair Display', 'family' => 'Playfair Display, serif'],
        'montserrat' => ['name' => 'Montserrat', 'family' => 'Montserrat, sans-serif'],
    ],

];
