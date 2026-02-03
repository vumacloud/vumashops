<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, BelongsToTenant;

    protected $guard = 'customer';

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'gender',
        'date_of_birth',
        'avatar',
        'is_verified',
        'is_active',
        'email_verified_at',
        'phone_verified_at',
        'accepts_marketing',
        'notes',
        'tags',
        'metadata',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'accepts_marketing' => 'boolean',
        'password' => 'hashed',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_verified' => false,
        'is_active' => true,
        'accepts_marketing' => false,
        'tags' => '[]',
        'metadata' => '{}',
    ];

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('grand_total');
    }

    public function getOrderCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getAverageOrderValueAttribute(): float
    {
        $orderCount = $this->order_count;
        if ($orderCount === 0) {
            return 0;
        }
        return $this->total_spent / $orderCount;
    }

    public function getLastOrderAttribute(): ?Order
    {
        return $this->orders()->latest()->first();
    }

    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function routeNotificationForAfricastalking(): string
    {
        return $this->phone;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAcceptsMarketing($query)
    {
        return $query->where('accepts_marketing', true);
    }

    public function hasPurchased(Product $product): bool
    {
        return $this->orders()
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->whereIn('status', ['completed', 'delivered'])
            ->exists();
    }
}
