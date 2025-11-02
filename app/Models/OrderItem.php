<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderItem extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'product_id',
        'provider_id',
        'quantity',
        'unit_price',
        'line_total',
        'line_discount',
        'total',
        'order_status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-set status to pending if not set
        static::creating(function (OrderItem $item) {
            if (empty($item->order_status)) {
                $item->order_status = self::STATUS_PENDING;
            }
        });

        // Note: Recalculation is handled manually in transitionTo() method to avoid event loops
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Products\Models\Product::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
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
                return [];
            }
            return array_diff($allStatuses, [$currentStatus]);
        }

        if ($user->hasRole('provider')) {
            // Provider can only update their own items
            if ($this->provider_id !== $user->id) {
                return [];
            }
            
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
            // User: can only cancel pending items in their own orders
            if ($this->order->user_id === $user->id && $currentStatus === self::STATUS_PENDING) {
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
     * Transition order item status with validation
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->order_status;
        $this->order_status = $newStatus;
        
        // Save directly using DB to avoid triggering updated event before recalculation
        DB::table('order_items')->where('id', $this->id)->update(['order_status' => $newStatus]);
        
        // Refresh model
        $this->refresh();

        // Manually trigger order recalculation
        $order = Order::find($this->order_id);
        if ($order) {
            $order->recalculateOrderStatus();
        }

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
            self::STATUS_SHIPPED => \App\Events\OrderItemShipped::class,
            self::STATUS_DELIVERED => \App\Events\OrderItemDelivered::class,
            self::STATUS_CANCELLED => \App\Events\OrderItemCancelled::class,
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
}
