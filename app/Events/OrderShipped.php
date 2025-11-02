<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShipped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public ?string $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?string $oldStatus = null)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
    }
}
