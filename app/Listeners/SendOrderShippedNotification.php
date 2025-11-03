<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShippedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderShippedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderShipped $event): void
    {
        $order = $event->order;
        
        if ($order->user && $order->user->email) {
            try {
                // Queue mail to respect provider limits; swallow transport rate-limit exceptions
                Mail::to($order->user->email)->queue(new OrderShippedMail($order));
            } catch (\Throwable $e) {
                \Log::warning('OrderShipped mail throttled or failed: '.$e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }
    }
}
