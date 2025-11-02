<?php

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderItemDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OrderItem $orderItem;
    public ?string $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(OrderItem $orderItem, ?string $oldStatus = null)
    {
        $this->orderItem = $orderItem;
        $this->oldStatus = $oldStatus;
    }
}
