<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

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
        // Tracking removed: no transitions
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
     * Transition order item status with validation
     * CRITICAL: This updates item status and triggers order recalculation
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
}