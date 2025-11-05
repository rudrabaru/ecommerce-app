<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    /**
     * In-process guard to prevent duplicate status emails per (order_id, status) in a single request lifecycle
     */
    protected static array $statusEmailDispatched = [];
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
        return [
            self::STATUS_PENDING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Check if a status transition is allowed for the current user
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions(), true);
    }

    /**
     * Transition order status with validation
     */
    public function transitionTo(string $newStatus): bool
    {
        if ($this->order_status === $newStatus) {
            return true;
        }
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->order_status;
        $this->order_status = $newStatus;
        $saved = $this->save();

        // Send one email only when full order-level status changes
        if ($saved && $oldStatus !== $newStatus) {
            $guardKey = $this->id . ':' . $newStatus;
            if (!isset(self::$statusEmailDispatched[$guardKey])) {
                self::$statusEmailDispatched[$guardKey] = true;
            try {
                dispatch(new \App\Jobs\SendOrderStatusUpdateEmail($this));
            } catch (\Throwable $_) {
                // swallow email errors
            }
            }
        }

        return $saved;
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
    public function getUniformItemStatus(): ?string
    {
        $statuses = $this->orderItems()->pluck('order_status')->unique();
        if ($statuses->count() === 0) {
            return null;
        }
        return $statuses->count() === 1 ? $statuses->first() : null;
    }

    /**
     * Recalculate order status based on item statuses
     * This only updates the aggregate order status, never removes provider_ids
     */
    public function recalculateOrderStatus(): void
    {
        $uniform = $this->getUniformItemStatus();
        if ($uniform && $uniform !== $this->order_status) {
            // Use transitionTo so that email is sent once here when aggregate changes
            $this->transitionTo($uniform);
        }
    }

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