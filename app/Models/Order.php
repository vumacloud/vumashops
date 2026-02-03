<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';
    const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_number',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'currency',
        'subtotal',
        'discount_amount',
        'coupon_code',
        'tax_amount',
        'shipping_amount',
        'shipping_method',
        'grand_total',
        'total_items',
        'total_quantity',
        'billing_address',
        'shipping_address',
        'customer_email',
        'customer_phone',
        'customer_name',
        'customer_notes',
        'admin_notes',
        'ip_address',
        'user_agent',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_items' => 'integer',
        'total_quantity' => 'integer',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'payment_status' => self::PAYMENT_PENDING,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'metadata' => '{}',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber($order->tenant_id);
            }
        });
    }

    public static function generateOrderNumber(int $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        $prefix = $tenant?->getSetting('order_prefix', 'ORD') ?? 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        $count = static::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d%s', $prefix, $date, $count, $random);
    }

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
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OrderComment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PROCESSING,
            self::STATUS_ON_HOLD,
        ]);
    }

    public function canBeRefunded(): bool
    {
        return $this->isPaid() && !in_array($this->payment_status, [
            self::PAYMENT_REFUNDED,
        ]);
    }

    public function updateStatus(string $status, ?string $comment = null): self
    {
        $oldStatus = $this->status;
        $this->status = $status;

        switch ($status) {
            case self::STATUS_SHIPPED:
                $this->shipped_at = now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
            case self::STATUS_COMPLETED:
                $this->completed_at = now();
                break;
            case self::STATUS_CANCELLED:
                $this->cancelled_at = now();
                break;
        }

        $this->save();

        if ($comment) {
            $this->comments()->create([
                'comment' => $comment,
                'status' => $status,
                'is_customer_notified' => true,
            ]);
        }

        return $this;
    }

    public function markAsPaid(string $reference = null): self
    {
        $this->payment_status = self::PAYMENT_PAID;
        if ($reference) {
            $this->payment_reference = $reference;
        }
        $this->save();

        return $this;
    }

    public function cancel(string $reason = null): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Restore stock
        foreach ($this->items as $item) {
            if ($item->product && $item->product->manage_stock) {
                $item->product->incrementStock($item->quantity);
            }
        }

        return $this;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getAmountDueAttribute(): float
    {
        return max(0, $this->grand_total - $this->total_paid);
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->tenant?->formatPrice($this->grand_total) ?? $this->currency . ' ' . number_format($this->grand_total, 2);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
