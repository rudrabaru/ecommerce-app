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
        $orderItem = $event->orderItem;
        $order = $orderItem->order;
        
        if ($order && $order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new OrderItemDeliveredMail($orderItem));
        }
    }
}
