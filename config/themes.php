<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | List of available storefront themes that tenants can choose from.
    |
    */

    'themes' => [
        'default' => [
            'name' => 'Default',
            'description' => 'Clean, minimal theme perfect for any store',
            'preview' => '/images/themes/default-preview.png',
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#6366F1',
                'accent' => '#F59E0B',
            ],
        ],
        'modern' => [
            'name' => 'Modern',
            'description' => 'Bold, contemporary design with dark mode support',
            'preview' => '/images/themes/modern-preview.png',
            'colors' => [
                'primary' => '#8B5CF6',
                'secondary' => '#EC4899',
                'accent' => '#10B981',
            ],
        ],
        'classic' => [
            'name' => 'Classic',
            'description' => 'Traditional e-commerce layout, familiar and trusted',
            'preview' => '/images/themes/classic-preview.png',
            'colors' => [
                'primary' => '#1F2937',
                'secondary' => '#374151',
                'accent' => '#DC2626',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    */

    'default' => 'default',

];
