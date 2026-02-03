<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales Overview';

    protected static ?int $sort = 2;

    public ?string $filter = '7days';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $days = match ($this->filter) {
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 7,
        };

        $data = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            if ($days > 30) {
                // Weekly grouping for longer periods
                if ($i % 7 !== 0) continue;
                $labels[] = $date->format('M d');
            } else {
                $labels[] = $date->format('M d');
            }

            $revenue = Order::whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            $data[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
