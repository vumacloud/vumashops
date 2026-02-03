<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Stores', Tenant::count())
                ->description('Active tenants on platform')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success')
                ->chart([7, 12, 18, 25, 30, 42, 55]),

            Stat::make('Active Subscriptions', Tenant::where('subscription_status', 'active')->count())
                ->description('Paid subscriptions')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make('Total Orders', Order::withoutGlobalScopes()->count())
                ->description('Across all stores')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('Total Revenue', '$' . number_format(Payment::withoutGlobalScopes()->where('status', 'completed')->sum('amount'), 2))
                ->description('Platform-wide revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
