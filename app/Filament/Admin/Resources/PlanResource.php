<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Platform Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price_monthly')
                            ->label('Monthly Price')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0),
                        Forms\Components\TextInput::make('price_yearly')
                            ->label('Yearly Price')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0),
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Trial Days')
                            ->numeric()
                            ->default(14),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Feature Limits')
                    ->schema([
                        Forms\Components\TextInput::make('features.max_products')
                            ->label('Max Products')
                            ->numeric()
                            ->default(100)
                            ->helperText('0 = unlimited'),
                        Forms\Components\TextInput::make('features.max_orders')
                            ->label('Max Orders/Month')
                            ->numeric()
                            ->default(1000)
                            ->helperText('0 = unlimited'),
                        Forms\Components\TextInput::make('features.max_staff')
                            ->label('Max Staff Users')
                            ->numeric()
                            ->default(3),
                        Forms\Components\TextInput::make('features.storage_gb')
                            ->label('Storage (GB)')
                            ->numeric()
                            ->default(5),
                        Forms\Components\Toggle::make('features.custom_domain')
                            ->label('Custom Domain')
                            ->default(true),
                        Forms\Components\Toggle::make('features.ssl_certificate')
                            ->label('SSL Certificate')
                            ->default(true),
                        Forms\Components\Toggle::make('features.analytics')
                            ->label('Analytics')
                            ->default(false),
                        Forms\Components\Toggle::make('features.api_access')
                            ->label('API Access')
                            ->default(false),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Monthly')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Yearly')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trial_days')
                    ->label('Trial')
                    ->suffix(' days'),
                Tables\Columns\TextColumn::make('tenants_count')
                    ->counts('tenants')
                    ->label('Tenants'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
