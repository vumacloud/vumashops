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

class Category extends Model implements HasMedia
{
    use HasFactory, HasSlug, SoftDeletes, BelongsToTenant, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'banner_image',
        'icon',
        'position',
        'is_active',
        'is_featured',
        'show_in_menu',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'additional',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_in_menu' => 'boolean',
        'additional' => 'array',
    ];

    protected $attributes = [
        'position' => 0,
        'is_active' => true,
        'is_featured' => false,
        'show_in_menu' => true,
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
        $this->addMediaCollection('image')
            ->singleFile()
            ->useFallbackUrl('/images/placeholder-category.png');

        $this->addMediaCollection('banner')
            ->singleFile();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function ancestors(): array
    {
        $ancestors = [];
        $category = $this;

        while ($category->parent) {
            $ancestors[] = $category->parent;
            $category = $category->parent;
        }

        return array_reverse($ancestors);
    }

    public function getFullPathAttribute(): string
    {
        $ancestors = $this->ancestors();
        $names = array_map(fn($c) => $c->name, $ancestors);
        $names[] = $this->name;

        return implode(' / ', $names);
    }

    public function getAllDescendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }

    public function getProductCount(): int
    {
        $categoryIds = $this->getAllDescendantIds();
        return Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->where('tenant_id', $this->tenant_id)->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function getDepth(): int
    {
        return count($this->ancestors());
    }

    public static function getTree(int $tenantId): array
    {
        return static::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    public static function getFlattenedTree(int $tenantId, ?int $parentId = null, int $depth = 0): array
    {
        $categories = static::where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->orderBy('position')
            ->get();

        $result = [];

        foreach ($categories as $category) {
            $category->depth = $depth;
            $result[] = $category;
            $result = array_merge($result, static::getFlattenedTree($tenantId, $category->id, $depth + 1));
        }

        return $result;
    }
}
