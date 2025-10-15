<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Fetch txn by order
        $txn = Transaction::where('gateway', 'razorpay')
            ->where('gateway_order_id', $razorpayOrderId)
            ->lockForUpdate()
            ->firstOrFail();

        $order = Order::lockForUpdate()->findOrFail($txn->order_id);

        // Auto-capture the payment in test mode
        // Amount should be in smallest unit and match the order amount
        try {
            $this->client->payment->fetch($paymentId);
            // Razorpay capture signature: capture($paymentId, $amount, array $params)
            $this->client->payment->capture($paymentId, $txn->amount, ['currency' => $txn->currency]);
        } catch (\Throwable $e) {
            // In demo/test flows, payment may already be captured by Checkout; continue marking paid
            \Illuminate\Support\Facades\Log::warning('Razorpay capture error (ignored in demo)', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }

        DB::transaction(function () use ($txn, $order, $paymentId) {
            $txn->update([
                'gateway_payment_id' => $paymentId,
                'status' => 'paid',
                'payload' => array_merge($txn->payload ?? [], [
                    'manual_confirm' => true,
                ]),
            ]);
            $order->update(['status' => 'paid']);

            // Update Payment row
            \App\Models\Payment::where('order_id', $order->id)
                ->where(function ($q) {
                    $q->where('gateway', 'razorpay')
                      ->orWhereHas('method', function ($q2) { $q2->where('name', 'razorpay'); });
                })
                ->update(['status' => 'paid']);
        });

        return [$order, $txn];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $orderId.'|'.$paymentId, config('services.razorpay.secret'));
        return hash_equals($expectedSignature, $signature);
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $entity = $payload['payload']['payment']['entity'] ?? null;
        if (!$event || !$entity) {
            Log::warning('Razorpay webhook: invalid payload');
            return;
        }

        $paymentId = $entity['id'] ?? null;
        $rOrderId = $entity['order_id'] ?? null;
        $amount = (int) ($entity['amount'] ?? 0);
        $currency = strtoupper($entity['currency'] ?? 'INR');

        DB::transaction(function () use ($event, $paymentId, $rOrderId, $amount, $currency, $payload) {
            $txn = Transaction::where('gateway', 'razorpay')
                ->where('gateway_order_id', $rOrderId)
                ->lockForUpdate()
                ->first();

            if (!$txn) {
                Log::warning('Razorpay webhook: transaction not found', ['rOrderId' => $rOrderId]);
                return;
            }

            $order = Order::lockForUpdate()->find($txn->order_id);
            if (!$order) {
                Log::warning('Razorpay webhook: order not found', ['order_id' => $txn->order_id]);
                return;
            }

            if (in_array($event, ['payment.captured'])) {
                $txn->update([
                    'gateway_payment_id' => $paymentId,
                    'status' => 'paid',
                    'amount' => $amount,
                    'currency' => $currency,
                    'payload' => array_merge($txn->payload ?? [], ['event' => $event, 'data' => $payload]),
                ]);
                $order->update(['status' => 'paid']);

                // Mark payment as paid
                Payment::where('order_id', $order->id)
                    ->where(function ($q) {
                        $q->where('gateway', 'razorpay')
                          ->orWhereHas('method', function ($q2) { $q2->where('name', 'razorpay'); });
                    })
                    ->update(['status' => 'paid']);

                // Clear cart for the user
                if ($order->user_id) {
                    if ($cart = \App\Models\Cart::where('user_id', $order->user_id)->first()) {
                        $cart->items()->delete();
                        $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                    }
                }
            } elseif (in_array($event, ['payment.failed'])) {
                $txn->update([
                    'gateway_payment_id' => $paymentId,
                    'status' => 'failed',
                    'payload' => array_merge($txn->payload ?? [], ['event' => $event, 'data' => $payload]),
                ]);
                $order->update(['status' => 'failed']);

                // Mark payment as failed
                Payment::where('order_id', $order->id)
                    ->where(function ($q) {
                        $q->where('gateway', 'razorpay')
                          ->orWhereHas('method', function ($q2) { $q2->where('name', 'razorpay'); });
                    })
                    ->update(['status' => 'failed']);
            }
        });
    }
}


