<?php

namespace App\Listeners;

use App\Events\OrderItemDelivered;
use App\Mail\OrderItemDeliveredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderItemDeliveredNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderItemDelivered $event): void
    {
        // Suppress per-item emails; a single email is sent when the order status changes at the order level
        return;
    }
}
