<?php

namespace App\Jobs;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public Order $order)
    {
    }

    public function handle(): void
    {
        $order = Order::with(['user'])->find($this->order->id);
        if (!$order || !$order->user || empty($order->user->email)) {
            return;
        }

        try {
            Mail::to($order->user->email)->send(new OrderStatusUpdatedMail($order));
        } catch (\Throwable $e) {
            Log::error('Order status update email failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}


