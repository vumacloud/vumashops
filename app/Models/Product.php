<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'quantity',
        'track_inventory',
        'allow_backorders',
        'weight',
        'weight_unit',
        'images',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'attributes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'track_inventory' => 'boolean',
        'allow_backorders' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'attributes' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->quantity > 0 || $this->allow_backorders;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }
        return (int) round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }
}
