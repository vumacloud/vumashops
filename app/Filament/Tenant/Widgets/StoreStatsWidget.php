<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayRevenue = Order::whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $monthRevenue = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        return [
            Stat::make('Today\'s Orders', $todayOrders)
                ->description('Orders received today')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Today\'s Revenue', format_price($todayRevenue))
                ->description('Revenue today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Monthly Revenue', format_price($monthRevenue))
                ->description('This month\'s revenue')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Total Products', Product::where('status', 'active')->count())
                ->description('Active products')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Total Customers', Customer::count())
                ->description('Registered customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Pending Orders', Order::where('status', 'pending')->count())
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getPendingOrdersColor()),
        ];
    }

    protected function getPendingOrdersColor(): string
    {
        $pending = Order::where('status', 'pending')->count();

        if ($pending > 10) return 'danger';
        if ($pending > 5) return 'warning';
        return 'success';
    }
}
