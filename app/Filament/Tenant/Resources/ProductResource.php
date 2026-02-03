<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'simple' => 'Simple Product',
                                        'configurable' => 'Configurable Product',
                                        'virtual' => 'Virtual Product',
                                        'downloadable' => 'Downloadable Product',
                                        'grouped' => 'Grouped Product',
                                        'bundle' => 'Bundle Product',
                                        'booking' => 'Booking Product',
                                    ])
                                    ->required()
                                    ->default('simple')
                                    ->reactive(),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', \Str::slug($state))),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('short_description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Section::make('Pricing')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix(fn () => tenant()?->getCurrencySymbol() ?? 'KSh'),

                                Forms\Components\TextInput::make('special_price')
                                    ->numeric()
                                    ->prefix(fn () => tenant()?->getCurrencySymbol() ?? 'KSh'),

                                Forms\Components\DateTimePicker::make('special_price_from')
                                    ->label('Special Price From'),

                                Forms\Components\DateTimePicker::make('special_price_to')
                                    ->label('Special Price To'),

                                Forms\Components\TextInput::make('cost')
                                    ->numeric()
                                    ->prefix(fn () => tenant()?->getCurrencySymbol() ?? 'KSh')
                                    ->helperText('Cost price for profit calculations'),
                            ])->columns(2),

                        Forms\Components\Section::make('Inventory')
                            ->schema([
                                Forms\Components\Toggle::make('manage_stock')
                                    ->label('Track Inventory')
                                    ->default(true)
                                    ->reactive(),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn ($get) => $get('manage_stock')),

                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->numeric()
                                    ->default(5)
                                    ->visible(fn ($get) => $get('manage_stock')),

                                Forms\Components\Toggle::make('allow_backorders')
                                    ->default(false)
                                    ->visible(fn ($get) => $get('manage_stock')),
                            ])->columns(2),

                        Forms\Components\Section::make('Shipping')
                            ->schema([
                                Forms\Components\TextInput::make('weight')
                                    ->numeric()
                                    ->suffix('kg'),

                                Forms\Components\TextInput::make('width')
                                    ->numeric()
                                    ->suffix('cm'),

                                Forms\Components\TextInput::make('height')
                                    ->numeric()
                                    ->suffix('cm'),

                                Forms\Components\TextInput::make('depth')
                                    ->numeric()
                                    ->suffix('cm'),
                            ])->columns(4),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'draft' => 'Draft',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Visible on Storefront')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured Product')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_new')
                                    ->label('Mark as New')
                                    ->default(false),
                            ]),

                        Forms\Components\Section::make('Categories')
                            ->schema([
                                Forms\Components\Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),
                            ]),

                        Forms\Components\Section::make('Images')
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                                    ->collection('images')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->image()
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('1024')
                                    ->imageResizeTargetHeight('1024')
                                    ->reorderable(),
                            ]),

                        Forms\Components\Section::make('SEO')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->maxLength(70),

                                Forms\Components\Textarea::make('meta_description')
                                    ->maxLength(160)
                                    ->rows(3),

                                Forms\Components\TextInput::make('meta_keywords')
                                    ->maxLength(255),
                            ])->collapsed(),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->circular()
                    ->stacked()
                    ->limit(3),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'simple' => 'gray',
                        'configurable' => 'primary',
                        'virtual' => 'info',
                        'downloadable' => 'success',
                        'grouped' => 'warning',
                        'bundle' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->money(fn () => tenant()?->currency ?? 'KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->color(fn ($record) => $record->isLowStock() ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'simple' => 'Simple',
                        'configurable' => 'Configurable',
                        'virtual' => 'Virtual',
                        'downloadable' => 'Downloadable',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'draft' => 'Draft',
                        'inactive' => 'Inactive',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
