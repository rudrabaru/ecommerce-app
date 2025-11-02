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
        $orderItem = $event->orderItem;
        $order = $orderItem->order;
        
        if ($order && $order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new OrderItemShippedMail($orderItem));
        }
    }
}
