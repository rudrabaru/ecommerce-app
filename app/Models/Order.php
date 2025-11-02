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
     */
    public function getAllowedTransitions(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        $currentStatus = $this->order_status ?? self::STATUS_PENDING;

        if ($user->hasRole('admin')) {
            // Admin can transition to any status except revert from delivered
            $allStatuses = [self::STATUS_PENDING, self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED];
            if ($currentStatus === self::STATUS_DELIVERED) {
                // Cannot revert from delivered
                return [];
            }
            return array_diff($allStatuses, [$currentStatus]);
        }

        if ($user->hasRole('provider')) {
            // Provider: pending → shipped, shipped → delivered, pending → cancelled
            $transitions = [];
            if ($currentStatus === self::STATUS_PENDING) {
                $transitions = [self::STATUS_SHIPPED, self::STATUS_CANCELLED];
            } elseif ($currentStatus === self::STATUS_SHIPPED) {
                $transitions = [self::STATUS_DELIVERED];
            }
            return $transitions;
        }

        if ($user->hasRole('user')) {
            // User: can only cancel pending orders
            if ($currentStatus === self::STATUS_PENDING && $this->user_id === $user->id) {
                return [self::STATUS_CANCELLED];
            }
        }

        return [];
    }

    /**
     * Check if a status transition is allowed for the current user
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = $this->getAllowedTransitions();
        return in_array($newStatus, $allowed);
    }

    /**
     * Transition order status with validation
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->order_status;
        $this->order_status = $newStatus;
        $this->save();

        // Fire appropriate event
        $eventClass = $this->getStatusEventClass($newStatus);
        if ($eventClass) {
            event(new $eventClass($this, $oldStatus));
        }

        return true;
    }

    /**
     * Get the event class for a status change
     */
    protected function getStatusEventClass(string $status): ?string
    {
        return match($status) {
            self::STATUS_SHIPPED => \App\Events\OrderShipped::class,
            self::STATUS_DELIVERED => \App\Events\OrderDelivered::class,
            self::STATUS_CANCELLED => \App\Events\OrderCancelled::class,
            default => null,
        };
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->order_status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_SHIPPED => 'bg-primary',
            self::STATUS_DELIVERED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string
    {
        return ucfirst($this->order_status ?? self::STATUS_PENDING);
    }

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
     * Recalculate order status based on item statuses
     */
    public function recalculateOrderStatus(): void
    {
        // Use fresh query to avoid stale data
        $items = OrderItem::where('order_id', $this->id)->get();
        
        if ($items->isEmpty()) {
            return;
        }

        $statusCounts = [
            self::STATUS_PENDING => 0,
            self::STATUS_SHIPPED => 0,
            self::STATUS_DELIVERED => 0,
            self::STATUS_CANCELLED => 0,
        ];

        foreach ($items as $item) {
            $status = $item->order_status ?? self::STATUS_PENDING;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        $totalItems = $items->count();
        
        // Get current status from database directly to avoid stale data
        $currentStatus = DB::table('orders')->where('id', $this->id)->value('order_status');
        $newStatus = $currentStatus;

        // Logic: Calculate aggregate status
        if ($statusCounts[self::STATUS_CANCELLED] === $totalItems) {
            // All items cancelled
            $newStatus = self::STATUS_CANCELLED;
        } elseif ($statusCounts[self::STATUS_DELIVERED] === $totalItems) {
            // All items delivered
            $newStatus = self::STATUS_DELIVERED;
        } elseif ($statusCounts[self::STATUS_DELIVERED] > 0 && $statusCounts[self::STATUS_CANCELLED] > 0) {
            // Some delivered, some cancelled - use "shipped" as intermediate
            $newStatus = self::STATUS_SHIPPED;
        } elseif ($statusCounts[self::STATUS_SHIPPED] > 0 || $statusCounts[self::STATUS_DELIVERED] > 0) {
            // At least one item shipped or delivered
            $newStatus = self::STATUS_SHIPPED;
        } elseif ($statusCounts[self::STATUS_PENDING] === $totalItems) {
            // All items pending
            $newStatus = self::STATUS_PENDING;
        } else {
            // Mixed states - default to shipped if any progress made
            $newStatus = self::STATUS_SHIPPED;
        }

        // Only update if status changed
        // Use direct DB query to get current status to avoid stale data
        $currentStatus = DB::table('orders')->where('id', $this->id)->value('order_status');
        
        if ($newStatus !== $currentStatus) {
            $oldStatus = $currentStatus;
            
            // Use direct DB update to avoid triggering model events
            DB::table('orders')->where('id', $this->id)->update(['order_status' => $newStatus]);
            
            // Refresh the model
            $this->refresh();

            // Fire order-level events if all items completed
            if ($newStatus === self::STATUS_DELIVERED && $oldStatus !== self::STATUS_DELIVERED) {
                event(new \App\Events\OrderDelivered($this, $oldStatus));
            } elseif ($newStatus === self::STATUS_CANCELLED && $oldStatus !== self::STATUS_CANCELLED) {
                event(new \App\Events\OrderCancelled($this, $oldStatus));
            }
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
