<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'parent_id',
        'sku',
        'name',
        'quantity',
        'price',
        'weight',
        'options',
        'additional',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'weight' => 'decimal:3',
        'options' => 'array',
        'additional' => 'array',
    ];

    protected $attributes = [
        'options' => '{}',
        'additional' => '{}',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CartItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CartItem::class, 'parent_id');
    }

    public function getTotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    public function getTotalWeightAttribute(): float
    {
        return ($this->weight ?? 0) * $this->quantity;
    }

    public function getOption(string $key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }

    public function setOption(string $key, $value): self
    {
        $options = $this->options ?? [];
        data_set($options, $key, $value);
        $this->options = $options;
        return $this;
    }

    public function isAvailable(): bool
    {
        if (!$this->product) {
            return false;
        }

        return $this->product->isInStock() && $this->product->status === 'active';
    }

    public function hasEnoughStock(): bool
    {
        if (!$this->product || !$this->product->manage_stock) {
            return true;
        }

        if ($this->product->allow_backorders) {
            return true;
        }

        return $this->product->quantity >= $this->quantity;
    }
}
