<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasFactory, HasSlug, SoftDeletes, BelongsToTenant, InteractsWithMedia;

    const TYPE_SIMPLE = 'simple';
    const TYPE_CONFIGURABLE = 'configurable';
    const TYPE_VIRTUAL = 'virtual';
    const TYPE_DOWNLOADABLE = 'downloadable';
    const TYPE_GROUPED = 'grouped';
    const TYPE_BUNDLE = 'bundle';
    const TYPE_BOOKING = 'booking';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'attribute_family_id',
        'type',
        'sku',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'special_price',
        'special_price_from',
        'special_price_to',
        'cost',
        'weight',
        'width',
        'height',
        'depth',
        'manage_stock',
        'quantity',
        'low_stock_threshold',
        'allow_backorders',
        'is_visible',
        'is_featured',
        'is_new',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'url_key',
        'tax_category_id',
        'attributes',
        'additional',
        'position',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'special_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'depth' => 'decimal:2',
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'manage_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'special_price_from' => 'datetime',
        'special_price_to' => 'datetime',
        'attributes' => 'array',
        'additional' => 'array',
        'position' => 'integer',
    ];

    protected $attributes = [
        'type' => self::TYPE_SIMPLE,
        'status' => 'active',
        'manage_stock' => true,
        'quantity' => 0,
        'low_stock_threshold' => 5,
        'allow_backorders' => false,
        'is_visible' => true,
        'is_featured' => false,
        'is_new' => false,
        'position' => 0,
        'attributes' => '{}',
        'additional' => '{}',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/placeholder-product.png');

        $this->addMediaCollection('downloads')
            ->singleFile();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function attributeFamily(): BelongsTo
    {
        return $this->belongsTo(AttributeFamily::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    public function groupedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'grouped_products', 'product_id', 'grouped_product_id')
            ->withPivot('quantity', 'sort_order');
    }

    public function bookingProduct(): HasOne
    {
        return $this->hasOne(BookingProduct::class);
    }

    public function downloadableLinks(): HasMany
    {
        return $this->hasMany(DownloadableLink::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->hasSpecialPrice()) {
            return $this->special_price;
        }
        return $this->price;
    }

    public function hasSpecialPrice(): bool
    {
        if (!$this->special_price) {
            return false;
        }

        $now = now();

        if ($this->special_price_from && $this->special_price_from->isFuture()) {
            return false;
        }

        if ($this->special_price_to && $this->special_price_to->isPast()) {
            return false;
        }

        return $this->special_price < $this->price;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasSpecialPrice()) {
            return null;
        }

        return round((($this->price - $this->special_price) / $this->price) * 100, 1);
    }

    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        if ($this->allow_backorders) {
            return true;
        }

        return $this->quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->manage_stock) {
            return false;
        }

        return $this->quantity <= $this->low_stock_threshold;
    }

    public function decrementStock(int $quantity): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        $this->decrement('quantity', $quantity);
        return true;
    }

    public function incrementStock(int $quantity): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        $this->increment('quantity', $quantity);
        return true;
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->where('status', 'approved')->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->where('status', 'approved')->count();
    }

    public function getAttribute(string $code)
    {
        return data_get($this->attributes, $code);
    }

    public function setProductAttribute(string $code, $value): self
    {
        $attributes = $this->attributes ?? [];
        data_set($attributes, $code, $value);
        $this->attributes = $attributes;
        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('manage_stock', false)
                ->orWhere('quantity', '>', 0)
                ->orWhere('allow_backorders', true);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
