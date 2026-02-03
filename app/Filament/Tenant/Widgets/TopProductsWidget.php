<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProductsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Selling Products';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->select('product_id', 'name')
                    ->selectRaw('SUM(quantity) as total_sold')
                    ->selectRaw('SUM(total) as total_revenue')
                    ->whereHas('order', function (Builder $query) {
                        $query->whereIn('status', ['completed', 'delivered', 'processing']);
                    })
                    ->groupBy('product_id', 'name')
                    ->orderByDesc('total_sold')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Units Sold')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money(fn () => tenant()?->currency ?? 'KES')
                    ->sortable(),
            ]);
    }
}
