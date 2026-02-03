<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'payment_method',
        'payment_reference',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'cancellation_reason',
        'auto_renew',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'auto_renew' => true,
        'metadata' => '{}',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    const BILLING_MONTHLY = 'monthly';
    const BILLING_YEARLY = 'yearly';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIAL])
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at && $this->ends_at->isPast());
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->ends_at) {
            return PHP_INT_MAX;
        }
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        return $this;
    }

    public function renew(): self
    {
        $duration = $this->billing_cycle === self::BILLING_YEARLY ? 365 : 30;

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($duration),
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        // Update tenant subscription status
        $this->tenant->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $this->ends_at,
        ]);

        return $this;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIAL])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '<=', now()->addDays($days))
            ->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }
}
