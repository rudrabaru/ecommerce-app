<?php
namespace App\Jobs;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $order;
    public $tries = 5; // Increased retry attempts
    public $backoff = [10, 30, 60, 120, 300]; // Backoff strategy: 10s, 30s, 60s, 2m, 5m
    public $timeout = 60; // Timeout for each attempt
    
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        try {
            // Refresh order to ensure we have latest data
            $order = Order::find($this->order->id);
            if (!$order) {
                Log::error('Order confirmation email job: Order not found', [
                    'order_id' => $this->order->id
                ]);
                return;
            }

            // Validate order has user and email
            if (!$order->user) {
                Log::error('Order confirmation email job: User not associated with order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
                return;
            }

            if (!$order->user->email) {
                Log::error('Order confirmation email job: User has no email address', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user->id
                ]);
                return;
            }

            // Send email
            Mail::to($order->user->email)
                ->send(new OrderConfirmationMail($order));

            Log::info('Order confirmation email sent successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'email' => $order->user->email,
                'attempt' => $this->attempts()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email on attempt ' . $this->attempts(), [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $e->getMessage(),
                'exception' => class_basename($e)
            ]);
            
            // Retry the job by re-throwing
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Order confirmation email job failed permanently after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_attempts' => $this->tries,
            'error' => $exception->getMessage(),
            'exception' => class_basename($exception)
        ]);

        // Optional: Update order status or send alert notification here
        // e.g., mark order with email_sent = false, or send admin alert
    }
}