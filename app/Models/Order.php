<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'user_id',
        'provider_ids',
        'total_amount',
        'order_status',
        'shipping_address',
        'shipping_address_id',
        'payment_method_id',
        'notes',
        'discount_code',
        'discount_amount'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'provider_ids' => 'array', // Cast JSON to array
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Products\Models\Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payment(): HasMany
    {
        // Some stores may allow multiple payment attempts per order
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all providers for this order (from provider_ids array)
     */
    public function providers()
    {
        if (!$this->provider_ids || empty($this->provider_ids)) {
            return collect([]);
        }
        return User::whereIn('id', $this->provider_ids)->get();
    }

    /**
     * Get the primary provider (first in the array)
     */
    public function getPrimaryProvider()
    {
        if ($this->provider_ids && count($this->provider_ids) > 0) {
            return User::find($this->provider_ids[0]);
        }
        return null;
    }

    /**
     * Get the primary provider ID
     */
    public function getPrimaryProviderId()
    {
        if ($this->provider_ids && count($this->provider_ids) > 0) {
            return $this->provider_ids[0];
        }
        return null;
    }

    /**
     * Get all unique provider IDs from this order
     */
    public function getAllProviderIds()
    {
        return $this->provider_ids ?? [];
    }

    /**
     * Add a provider ID to the array
     */
    public function addProviderId($providerId)
    {
        $providerIds = $this->provider_ids ?? [];
        if (!in_array($providerId, $providerIds)) {
            $providerIds[] = $providerId;
            $this->provider_ids = $providerIds;
        }
    }

    /**
     * Check if order contains items from a specific provider
     */
    public function containsProvider($providerId)
    {
        return in_array($providerId, $this->provider_ids ?? []);
    }

    /**
     * Get valid status transitions for the current user role
     * NOTE: For providers, these transitions apply to their ITEMS, not the order itself
     */
    public function getAllowedTransitions(): array
    {
        // Tracking removed: no transitions available
        return [];
    }

    /**
     * Check if a status transition is allowed for the current user
     */
    public function canTransitionTo(string $newStatus): bool
    {
        // Tracking removed
        return false;
    }

    /**
     * Transition order status with validation
     */
    public function transitionTo(string $newStatus): bool
    {
        // Tracking removed: do nothing
        return false;
    }

    /**
     * Get the event class for a status change
     */
    protected function getStatusEventClass(string $status): ?string { return null; }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string { return 'bg-secondary'; }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string { return 'N/A'; }

    /**
     * Get order progress percentage (for timeline)
     */
    public function getProgressPercentage(): int
    {
        return match($this->order_status) {
            self::STATUS_PENDING => 0,
            self::STATUS_SHIPPED => 50,
            self::STATUS_DELIVERED => 100,
            self::STATUS_CANCELLED => 0,
            default => 0,
        };
    }

    /**
     * Returns item status if all items share the same status; otherwise null.
     */
    public function getUniformItemStatus(): ?string { return null; }

    /**
     * Recalculate order status based on item statuses
     * This only updates the aggregate order status, never removes provider_ids
     */
    public function recalculateOrderStatus(): void { /* tracking removed */ }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
            if (empty($order->order_status)) {
                $order->order_status = self::STATUS_PENDING;
            }
        });
    }
}