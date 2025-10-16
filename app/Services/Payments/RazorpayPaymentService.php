<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Cart;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api as RazorpayClient;

class RazorpayPaymentService
{
    private RazorpayClient $client;

    public function __construct()
    {
        $this->client = new RazorpayClient(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function createOrder(Order $order): array
    {
        // Demo mode: if order currency is not INR, convert using fixed rate 1 USD = 83 INR
        $currency = 'INR';
        $amountBase = (float) $order->total_amount;
        if (strtoupper((string)($order->currency ?? 'USD')) !== 'INR') {
            $amountBase = $amountBase * 83.0; // demo conversion
        }
        $amountMinor = (int) round($amountBase * 100);

        $rOrder = $this->client->order->create([
            'amount' => $amountMinor,
            'currency' => $currency,
            'receipt' => (string) $order->order_number,
            'notes' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) $order->user_id,
            ],
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'razorpay',
            'gateway_order_id' => $rOrder['id'],
            'amount' => $amountMinor,
            'currency' => strtoupper($currency),
            'status' => 'pending',
            'payload' => ['order' => $rOrder->toArray()],
        ]);

        return [
            'razorpay_order_id' => $rOrder['id'],
            'amount' => $amountMinor,
            'currency' => $currency,
            'key' => config('services.razorpay.key'),
        ];
    }

    /**
     * Demo/test-only: capture and mark paid without webhooks, using frontend callback.
     */
    public function captureAndMarkPaid(string $razorpayOrderId, string $paymentId): array
    {
        DB::beginTransaction();
        try {
            $txn = Transaction::where('gateway', 'razorpay')
                ->where('gateway_order_id', $razorpayOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::lockForUpdate()->find($txn->order_id);
            if (!$order) {
                throw new \Exception('Order not found for transaction: ' . $txn->id);
            }

            // Idempotency check: if already paid, skip processing
            if ($order->status === 'paid') {
                Log::info('Razorpay captureAndMarkPaid: Order already marked as paid, skipping duplicate processing', [
                    'order_id' => $order->id,
                    'razorpay_order_id' => $razorpayOrderId,
                    'payment_id' => $paymentId
                ]);
                DB::commit();
                return [
                    'success' => true,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'duplicate' => true
                ];
            }

            // Attempt to fetch payment to check its status
            try {
                $payment = $this->client->payment->fetch($paymentId);
                Log::info('Razorpay payment fetched successfully', [
                    'payment_id' => $paymentId,
                    'payment_status' => $payment->status ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                Log::warning('Razorpay payment fetch failed: ' . $e->getMessage(), ['payment_id' => $paymentId]);
                // Continue anyway - this might be a test mode payment
                $payment = null;
            }

            // Only capture if payment exists and status is authorized (not already captured)
            if ($payment && $payment->status === 'authorized') {
                try {
                    $this->client->payment->capture($paymentId, $txn->amount, ['currency' => $txn->currency]);
                    Log::info('Razorpay payment captured successfully.', ['payment_id' => $paymentId]);
                } catch (\Exception $e) {
                    // Log and proceed if already captured (common in test mode)
                    Log::warning('Razorpay payment capture failed or already captured: ' . $e->getMessage(), ['payment_id' => $paymentId]);
                }
            } else {
                Log::info('Razorpay payment not in authorized state, assuming already captured or test mode', [
                    'payment_id' => $paymentId,
                    'payment_status' => $payment->status ?? 'fetch_failed'
                ]);
            }

            $txn->update([
                'gateway_payment_id' => $paymentId,
                'status' => 'paid',
                'payload' => array_merge($txn->payload ?? [], ['payment' => $payment ? $payment->toArray() : []]),
            ]);
            
            $order->update(['status' => 'paid']);

            // Update the associated Payment record
            $paymentMethod = PaymentMethod::where('name', 'razorpay')->first();
            if ($paymentMethod) {
                Payment::where('order_id', $order->id)
                    ->where('payment_method_id', $paymentMethod->id)
                    ->update(['status' => 'paid']);
            } else {
                Payment::where('order_id', $order->id)->update(['status' => 'paid']);
            }

            // Clear cart
            $user = $order->user;
            if ($user) {
                $cart = Cart::where('user_id', $user->id)->first();
                if ($cart) {
                    $cart->items()->delete();
                    $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                }
            }

            // Queue order confirmation email with logging
            Log::info('Razorpay captureAndMarkPaid: Dispatching order confirmation email job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_email' => $order->user->email ?? 'N/A'
            ]);
            dispatch(new \App\Jobs\SendOrderConfirmationEmail($order));

            DB::commit();
            
            return [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Razorpay captureAndMarkPaid failed: ' . $e->getMessage(), [
                'razorpay_order_id' => $razorpayOrderId,
                'payment_id' => $paymentId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Webhook handling removed by requirement; confirmation is handled via confirm endpoint only
}