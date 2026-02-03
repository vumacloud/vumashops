<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', \Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone'),

                        Forms\Components\TextInput::make('domain')
                            ->url()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('subdomain')
                            ->unique(ignoreRecord: true)
                            ->suffix('.vumashops.com'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Location & Currency')
                    ->schema([
                        Forms\Components\Select::make('country')
                            ->options(config('app.supported_countries'))
                            ->default('KE')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $set('currency', country_currency($state))),

                        Forms\Components\Select::make('currency')
                            ->options(array_combine(
                                array_keys(config('app.supported_currencies')),
                                array_map(fn($c) => $c['name'] . ' (' . $c['symbol'] . ')', config('app.supported_currencies'))
                            ))
                            ->default('KES')
                            ->required(),

                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Africa/Nairobi' => 'East Africa Time (Nairobi)',
                                'Africa/Lagos' => 'West Africa Time (Lagos)',
                                'Africa/Johannesburg' => 'South Africa Time (Johannesburg)',
                                'Africa/Cairo' => 'Egypt Time (Cairo)',
                                'Africa/Casablanca' => 'Morocco Time (Casablanca)',
                            ])
                            ->default('Africa/Nairobi'),

                        Forms\Components\TextInput::make('address'),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\TextInput::make('state'),
                        Forms\Components\TextInput::make('postal_code'),
                    ])->columns(2),

                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->preload(),

                        Forms\Components\Select::make('subscription_status')
                            ->options([
                                'trial' => 'Trial',
                                'active' => 'Active',
                                'past_due' => 'Past Due',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->default('trial'),

                        Forms\Components\DateTimePicker::make('trial_ends_at'),

                        Forms\Components\DateTimePicker::make('subscription_ends_at'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('tenants/logos'),

                        Forms\Components\FileUpload::make('favicon')
                            ->image()
                            ->directory('tenants/favicons'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->badge(),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('country')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Orders'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('country')
                    ->options(config('app.supported_countries')),

                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),

                Tables\Filters\TernaryFilter::make('is_active'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('login')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->url(fn (Tenant $record) => 'https://' . ($record->domain ?? $record->subdomain . '.vumashops.com') . '/admin')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
