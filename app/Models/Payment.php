<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    const METHOD_PAYSTACK = 'paystack';
    const METHOD_FLUTTERWAVE = 'flutterwave';
    const METHOD_MPESA_KENYA = 'mpesa_kenya';
    const METHOD_MPESA_TANZANIA = 'mpesa_tanzania';
    const METHOD_MTN_MOMO = 'mtn_momo';
    const METHOD_AIRTEL_MONEY = 'airtel_money';
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'customer_id',
        'subscription_id',
        'reference',
        'gateway_reference',
        'method',
        'gateway',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'status',
        'phone_number',
        'email',
        'description',
        'gateway_response',
        'metadata',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'fee' => 0,
        'gateway_response' => '{}',
        'metadata' => '{}',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $payment->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $prefix = config('payments.reference_prefix', 'VS');
        return $prefix . '_' . strtoupper(uniqid()) . '_' . time();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function markAsProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    public function markAsCompleted(?string $gatewayReference = null, ?array $response = null): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'gateway_reference' => $gatewayReference ?? $this->gateway_reference,
            'gateway_response' => $response ?? $this->gateway_response,
            'paid_at' => now(),
            'net_amount' => $this->amount - $this->fee,
        ]);

        // Update order if exists
        if ($this->order) {
            $this->order->markAsPaid($this->reference);
        }

        return $this;
    }

    public function markAsFailed(?string $reason = null, ?array $response = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_response' => $response ?? $this->gateway_response,
            'failed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);

        return $this;
    }

    public function markAsRefunded(?float $amount = null): self
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);

        return $this;
    }

    public function getGatewayName(): string
    {
        return match($this->gateway) {
            self::METHOD_PAYSTACK => 'Paystack',
            self::METHOD_FLUTTERWAVE => 'Flutterwave',
            self::METHOD_MPESA_KENYA => 'M-Pesa Kenya',
            self::METHOD_MPESA_TANZANIA => 'M-Pesa Tanzania',
            self::METHOD_MTN_MOMO => 'MTN Mobile Money',
            self::METHOD_AIRTEL_MONEY => 'Airtel Money',
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            default => ucfirst($this->gateway),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_REFUNDED => 'primary',
            default => 'secondary',
        };
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
