<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\OrderDeliveredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderDeliveredNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        
        if ($order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new OrderDeliveredMail($order));
        }
    }
}
