<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'session_id',
        'currency',
        'coupon_code',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'shipping_method',
        'billing_address',
        'shipping_address',
        'customer_email',
        'customer_phone',
        'customer_name',
        'notes',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'metadata' => '{}',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function getGrandTotalAttribute(): float
    {
        return $this->subtotal
            - $this->discount_amount
            + $this->tax_amount
            + $this->shipping_amount;
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->count();
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function addItem(Product $product, int $quantity = 1, array $options = []): CartItem
    {
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->where('options', json_encode($options))
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem->fresh();
        }

        return $this->items()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->effective_price,
            'quantity' => $quantity,
            'options' => $options,
        ]);
    }

    public function updateItemQuantity(int $itemId, int $quantity): ?CartItem
    {
        $item = $this->items()->find($itemId);

        if (!$item) {
            return null;
        }

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    public function removeItem(int $itemId): bool
    {
        return $this->items()->where('id', $itemId)->delete() > 0;
    }

    public function clear(): bool
    {
        return $this->items()->delete() > 0;
    }

    public function applyCoupon(Coupon $coupon): self
    {
        $this->coupon_code = $coupon->code;
        $this->discount_amount = $coupon->calculateDiscount($this->subtotal);
        $this->save();

        return $this;
    }

    public function removeCoupon(): self
    {
        $this->coupon_code = null;
        $this->discount_amount = 0;
        $this->save();

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function refresh(): self
    {
        // Update prices from products
        foreach ($this->items as $item) {
            if ($item->product) {
                $item->update([
                    'price' => $item->product->effective_price,
                    'name' => $item->product->name,
                ]);
            }
        }

        // Recalculate totals
        $this->calculateTax();
        $this->save();

        return $this;
    }

    public function calculateTax(): self
    {
        $tenant = $this->tenant;
        if (!$tenant || !$tenant->getSetting('tax_enabled', false)) {
            $this->tax_amount = 0;
            return $this;
        }

        $taxRate = $tenant->getSetting('tax_rate', 0) / 100;
        $this->tax_amount = round($this->subtotal * $taxRate, 2);

        return $this;
    }

    public function toOrder(array $additional = []): Order
    {
        $orderData = array_merge([
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency ?? $this->tenant?->currency ?? 'KES',
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'coupon_code' => $this->coupon_code,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'shipping_method' => $this->shipping_method,
            'grand_total' => $this->grand_total,
            'total_items' => $this->total_items,
            'total_quantity' => $this->total_quantity,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_name' => $this->customer_name,
            'customer_notes' => $this->notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $additional);

        $order = Order::create($orderData);

        foreach ($this->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'type' => $item->product?->type ?? 'simple',
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->price * $item->quantity,
                'options' => $item->options,
            ]);

            // Decrement stock
            if ($item->product && $item->product->manage_stock) {
                $item->product->decrementStock($item->quantity);
            }
        }

        return $order;
    }

    public static function getOrCreate(?Customer $customer = null, ?string $sessionId = null, int $tenantId = null): self
    {
        if ($customer) {
            $cart = static::where('customer_id', $customer->id)
                ->where('tenant_id', $tenantId ?? $customer->tenant_id)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        if ($sessionId) {
            $cart = static::where('session_id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->whereNull('customer_id')
                ->first();

            if ($cart) {
                if ($customer) {
                    $cart->update(['customer_id' => $customer->id]);
                }
                return $cart;
            }
        }

        return static::create([
            'tenant_id' => $tenantId ?? $customer?->tenant_id,
            'customer_id' => $customer?->id,
            'session_id' => $sessionId,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
