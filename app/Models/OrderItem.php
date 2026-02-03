<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'parent_id',
        'sku',
        'type',
        'name',
        'quantity',
        'price',
        'total',
        'tax_amount',
        'tax_percent',
        'discount_amount',
        'discount_percent',
        'weight',
        'options',
        'additional',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'weight' => 'decimal:3',
        'options' => 'array',
        'additional' => 'array',
    ];

    protected $attributes = [
        'tax_amount' => 0,
        'tax_percent' => 0,
        'discount_amount' => 0,
        'discount_percent' => 0,
        'options' => '{}',
        'additional' => '{}',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrderItem::class, 'parent_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    public function getGrandTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    public function getOption(string $key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }
}
