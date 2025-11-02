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
        $orderItem = $event->orderItem;
        $order = $orderItem->order;
        
        if ($order && $order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new OrderItemCancelledMail($orderItem));
        }
    }
}
