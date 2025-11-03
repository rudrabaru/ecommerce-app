<?php

namespace App\Listeners;

use App\Events\OrderItemCancelled;
use App\Mail\OrderItemCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderItemCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderItemCancelled $event): void
    {
        // Suppress per-item emails; a single email is sent when the order status changes at the order level
        return;
    }
}
