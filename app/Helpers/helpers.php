<?php

use App\Models\Tenant;
use App\Providers\TenancyServiceProvider;
use App\Services\Payments\PaymentManager;
use App\Services\Notifications\BrevoEmailService;
use App\Services\Notifications\AfricasTalkingSmsService;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant.
     */
    function tenant(): ?Tenant
    {
        return TenancyServiceProvider::getTenant();
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Get the current tenant ID.
     */
    function tenant_id(): ?int
    {
        return tenant()?->id;
    }
}

if (!function_exists('is_tenant')) {
    /**
     * Check if we're in a tenant context.
     */
    function is_tenant(): bool
    {
        return TenancyServiceProvider::hasTenant();
    }
}

if (!function_exists('tenant_asset')) {
    /**
     * Generate a tenant-specific asset URL.
     */
    function tenant_asset(string $path): string
    {
        $tenant = tenant();

        if (!$tenant) {
            return asset($path);
        }

        return asset("tenants/{$tenant->id}/{$path}");
    }
}

if (!function_exists('format_price')) {
    /**
     * Format a price with currency.
     */
    function format_price(float $amount, ?string $currency = null): string
    {
        $tenant = tenant();
        $currency = $currency ?? $tenant?->currency ?? 'KES';

        $currencies = config('app.supported_currencies', []);
        $config = $currencies[$currency] ?? ['symbol' => $currency, 'decimals' => 2];

        return $config['symbol'] . ' ' . number_format($amount, $config['decimals']);
    }
}

if (!function_exists('payments')) {
    /**
     * Get the payment manager instance.
     */
    function payments(): PaymentManager
    {
        return app(PaymentManager::class);
    }
}

if (!function_exists('brevo')) {
    /**
     * Get the Brevo email service instance.
     */
    function brevo(): BrevoEmailService
    {
        return app(BrevoEmailService::class);
    }
}

if (!function_exists('sms')) {
    /**
     * Get the Africa's Talking SMS service instance.
     */
    function sms(): AfricasTalkingSmsService
    {
        return app(AfricasTalkingSmsService::class);
    }
}

if (!function_exists('generate_order_number')) {
    /**
     * Generate a unique order number.
     */
    function generate_order_number(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? tenant_id();
        $prefix = tenant()?->getSetting('order_prefix', 'ORD') ?? 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return sprintf('%s-%s-%s', $prefix, $date, $random);
    }
}

if (!function_exists('format_phone')) {
    /**
     * Format a phone number for a specific country.
     */
    function format_phone(string $phone, string $countryCode = '254'): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = $countryCode . substr($phone, 1);
        } elseif (!str_starts_with($phone, $countryCode) && strlen($phone) <= 10) {
            $phone = $countryCode . $phone;
        }

        return '+' . $phone;
    }
}

if (!function_exists('country_currency')) {
    /**
     * Get the default currency for a country.
     */
    function country_currency(string $countryCode): string
    {
        $currencies = [
            'KE' => 'KES',
            'TZ' => 'TZS',
            'UG' => 'UGX',
            'NG' => 'NGN',
            'GH' => 'GHS',
            'ZA' => 'ZAR',
            'RW' => 'RWF',
            'ZM' => 'ZMW',
            'MW' => 'MWK',
            'ET' => 'ETB',
            'SN' => 'XOF',
            'CI' => 'XOF',
            'CM' => 'XAF',
        ];

        return $currencies[$countryCode] ?? 'USD';
    }
}

if (!function_exists('african_countries')) {
    /**
     * Get list of supported African countries.
     */
    function african_countries(): array
    {
        return config('app.supported_countries', [
            'KE' => 'Kenya',
            'TZ' => 'Tanzania',
            'UG' => 'Uganda',
            'NG' => 'Nigeria',
            'GH' => 'Ghana',
            'ZA' => 'South Africa',
            'RW' => 'Rwanda',
        ]);
    }
}

if (!function_exists('clean_price')) {
    /**
     * Clean and convert a price string to float.
     */
    function clean_price($price): float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        $price = preg_replace('/[^0-9.,]/', '', $price);
        $price = str_replace(',', '', $price);

        return (float) $price;
    }
}

if (!function_exists('human_filesize')) {
    /**
     * Convert bytes to human readable format.
     */
    function human_filesize(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
    }
}
