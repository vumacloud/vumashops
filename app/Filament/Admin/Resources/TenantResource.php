<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\BagistoProvisioner;
use App\Services\NginxConfigGenerator;
use App\Services\SslManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Tenant Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('domain')
                            ->label('Domain')
                            ->placeholder('mystore.com')
                            ->helperText('Custom domain for this store (point DNS A record to server first)')
                            ->required()
                            ->visibleOn('create'),
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Admin Password')
                            ->password()
                            ->minLength(8)
                            ->helperText('Password for Bagisto admin panel (min 8 chars)')
                            ->required()
                            ->visibleOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Localization')
                    ->schema([
                        Forms\Components\Select::make('country')
                            ->options([
                                'KE' => 'Kenya',
                                'TZ' => 'Tanzania',
                                'UG' => 'Uganda',
                                'RW' => 'Rwanda',
                                'NG' => 'Nigeria',
                                'GH' => 'Ghana',
                                'ZA' => 'South Africa',
                            ])
                            ->default('KE')
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'KES' => 'KES - Kenyan Shilling',
                                'TZS' => 'TZS - Tanzanian Shilling',
                                'UGX' => 'UGX - Ugandan Shilling',
                                'RWF' => 'RWF - Rwandan Franc',
                                'NGN' => 'NGN - Nigerian Naira',
                                'GHS' => 'GHS - Ghanaian Cedi',
                                'ZAR' => 'ZAR - South African Rand',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->default('KES')
                            ->required(),
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
                                'Africa/Dar_es_Salaam' => 'Africa/Dar_es_Salaam (EAT)',
                                'Africa/Kampala' => 'Africa/Kampala (EAT)',
                                'Africa/Kigali' => 'Africa/Kigali (CAT)',
                                'Africa/Lagos' => 'Africa/Lagos (WAT)',
                                'Africa/Accra' => 'Africa/Accra (GMT)',
                                'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
                            ])
                            ->default('Africa/Nairobi')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->required(),
                        Forms\Components\Select::make('subscription_status')
                            ->options([
                                'trial' => 'Trial',
                                'active' => 'Active',
                                'cancelled' => 'Cancelled',
                                'suspended' => 'Suspended',
                                'expired' => 'Expired',
                            ])
                            ->default('trial')
                            ->required(),
                        Forms\Components\DateTimePicker::make('trial_ends_at'),
                        Forms\Components\DateTimePicker::make('subscription_ends_at'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('WHMCS Integration')
                    ->schema([
                        Forms\Components\TextInput::make('whmcs_service_id')
                            ->numeric(),
                        Forms\Components\TextInput::make('whmcs_client_id')
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->badge(),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'danger',
                        'cancelled', 'expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('country')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domain')
                    ->limit(30),
                Tables\Columns\IconColumn::make('bagisto_installed_at')
                    ->label('Bagisto')
                    ->boolean()
                    ->getStateUsing(fn (Tenant $record) => $record->isBagistoInstalled()),
                Tables\Columns\TextColumn::make('storefront_type')
                    ->label('Storefront')
                    ->badge()
                    ->toggleable(),
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
                        'cancelled' => 'Cancelled',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('admin')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Tenant $record) => $record->getAdminUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Tenant $record) => $record->isBagistoInstalled()),
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record) => !$record->isSuspended())
                    ->action(fn (Tenant $record) => $record->suspend('Suspended by admin')),
                Tables\Actions\Action::make('unsuspend')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record) => $record->isSuspended())
                    ->action(fn (Tenant $record) => $record->unsuspend()),
                Tables\Actions\Action::make('provision')
                    ->label('Provision Bagisto')
                    ->icon('heroicon-o-server-stack')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Provision Bagisto Installation')
                    ->modalDescription('This will install a fresh Bagisto e-commerce platform for this tenant. This process may take several minutes.')
                    ->form([
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Admin Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->helperText('Password for Bagisto admin panel'),
                    ])
                    ->visible(fn (Tenant $record) => !$record->isBagistoInstalled() && $record->getPrimaryDomain())
                    ->action(function (Tenant $record, array $data) {
                        try {
                            $provisioner = app(BagistoProvisioner::class);
                            $nginxGenerator = app(NginxConfigGenerator::class);

                            // Generate nginx config first
                            $nginxGenerator->generate($record);

                            // Provision Bagisto
                            $provisioner->provision($record, [
                                'admin_email' => $record->email,
                                'admin_password' => $data['admin_password'],
                                'storefront_type' => 'bagisto_default',
                            ]);

                            Notification::make()
                                ->title('Bagisto Provisioned')
                                ->body('Bagisto has been installed successfully for ' . $record->name)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Provisioning Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Store Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('domains.domain')
                            ->label('Domain'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Bagisto Installation')
                    ->schema([
                        Infolists\Components\IconEntry::make('bagisto_installed')
                            ->label('Installed')
                            ->boolean()
                            ->getStateUsing(fn (Tenant $record) => $record->isBagistoInstalled()),
                        Infolists\Components\TextEntry::make('bagisto_version')
                            ->label('Version'),
                        Infolists\Components\TextEntry::make('storefront_type')
                            ->label('Storefront')
                            ->badge(),
                        Infolists\Components\TextEntry::make('bagisto_installed_at')
                            ->label('Installed At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('admin_url')
                            ->label('Admin Panel')
                            ->getStateUsing(fn (Tenant $record) => $record->getAdminUrl())
                            ->url(fn (Tenant $record) => $record->getAdminUrl(), shouldOpenInNewTab: true),
                        Infolists\Components\TextEntry::make('api_url')
                            ->label('GraphQL API')
                            ->getStateUsing(fn (Tenant $record) => $record->getApiUrl())
                            ->url(fn (Tenant $record) => $record->getApiUrl(), shouldOpenInNewTab: true),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Subscription')
                    ->schema([
                        Infolists\Components\TextEntry::make('plan.name')
                            ->badge(),
                        Infolists\Components\TextEntry::make('subscription_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'trial' => 'info',
                                'suspended' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('subscription_ends_at')
                            ->dateTime(),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('SSL/Domain')
                    ->schema([
                        Infolists\Components\TextEntry::make('ssl_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'issuing', 'verifying' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('ssl_issued_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('ssl_expires_at')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
}
