<?php

namespace App\Listeners;

use App\Events\OrderItemShipped;
use App\Mail\OrderItemShippedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderItemShippedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderItemShipped $event): void
    {
        // Suppress per-item emails; a single email is sent when the order status changes at the order level
        return;
    }
}
