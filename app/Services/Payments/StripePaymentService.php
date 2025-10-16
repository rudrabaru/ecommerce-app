<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Cart;
use App\Mail\OrderConfirmationMail;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Order $order): array
    {
        $amountMinor = (int) round(((float) $order->total_amount) * 100);
        $currency = 'usd';

        $intent = $this->client->paymentIntents->create([
            'amount' => $amountMinor,
            'currency' => $currency,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'user_id' => (string) $order->user_id,
            ],
        ], [ 'idempotency_key' => 'order_'.$order->id ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'stripe',
            'gateway_payment_id' => $intent->id,
            'amount' => $amountMinor,
            'currency' => strtoupper($currency),
            'status' => 'pending',
            'payload' => ['intent' => $intent->toArray()],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');
        $event = Webhook::constructEvent($payload, $signature, $secret);

        $type = $event->type;
        $data = $event->data->object;

        if ($type === 'payment_intent.succeeded' || $type === 'payment_intent.payment_failed') {
            $intentId = $data->id;
            $metadata = $data->metadata ?? [];
            $orderId = (int) ($metadata['order_id'] ?? 0);

            DB::transaction(function () use ($intentId, $orderId, $type, $data) {
                $order = Order::lockForUpdate()->find($orderId);
                if (!$order) {
                    Log::warning('Stripe webhook: Order not found', ['order_id' => $orderId]);
                    return;
                }

                // Idempotency: if already marked paid/failed accordingly, skip
                if ($type === 'payment_intent.succeeded' && $order->status === 'paid') {
                    Log::info('Stripe webhook: Order already marked as paid, skipping duplicate processing', ['order_id' => $orderId]);
                    return;
                }
                if ($type === 'payment_intent.payment_failed' && $order->status === 'failed') {
                    Log::info('Stripe webhook: Order already marked as failed, skipping duplicate processing', ['order_id' => $orderId]);
                    return;
                }

                $txn = Transaction::where('gateway', 'stripe')
                    ->where('gateway_payment_id', $intentId)
                    ->lockForUpdate()
                    ->first();

                if (!$txn) {
                    $txn = Transaction::create([
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'gateway' => 'stripe',
                        'gateway_payment_id' => $intentId,
                        'amount' => (int) $data->amount_received,
                        'currency' => strtoupper($data->currency),
                        'status' => 'pending',
                    ]);
                }

                if ($type === 'payment_intent.succeeded') {
                    $txn->update([
                        'status' => 'paid',
                        'processed_via' => 'webhook',
                        'payload' => array_merge($txn->payload ?? [], ['event' => $type, 'data' => $data->toArray()]),
                    ]);
                    $order->update(['status' => 'paid']);

                    // Mark payment as paid via method name to avoid relying on non-existent columns
                    Payment::where('order_id', $order->id)
                        ->whereHas('paymentMethod', function ($q) { $q->where('name', 'stripe'); })
                        ->update(['status' => 'paid']);

                    // Clear cart for the user (if any)
                    if ($order->user_id) {
                        if ($cart = Cart::where('user_id', $order->user_id)->first()) {
                            $cart->items()->delete();
                            $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                        }
                    }

                    // Queue order confirmation email with logging
                    Log::info('Stripe webhook: Dispatching order confirmation email job', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_email' => $order->user->email ?? 'N/A'
                    ]);
                    dispatch(new \App\Jobs\SendOrderConfirmationEmail($order));
                } else {
                    $txn->update([
                        'status' => 'failed',
                        'processed_via' => 'webhook',
                        'error_code' => $data->last_payment_error->code ?? null,
                        'error_message' => $data->last_payment_error->message ?? null,
                        'payload' => array_merge($txn->payload ?? [], ['event' => $type, 'data' => $data->toArray()]),
                    ]);
                    $order->update(['status' => 'failed']);

                    // Mark payment as failed via method name
                    Payment::where('order_id', $order->id)
                        ->whereHas('paymentMethod', function ($q) { $q->where('name', 'stripe'); })
                        ->update(['status' => 'failed']);
                    
                    Log::error('Stripe webhook: Payment failed for order', [
                        'order_id' => $orderId,
                        'error_code' => $data->last_payment_error->code ?? null,
                        'error_message' => $data->last_payment_error->message ?? null
                    ]);
                }
            });
        }
    }

    /**
     * Demo/local helper: confirm success without relying on webhooks.
     * Retrieves the PaymentIntent and marks Order/Payment/Transaction as paid,
     * clears cart and sends confirmation email.
     */
    public function confirmAndMarkPaid(string $paymentIntentId): array
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId);

        $orderId = (int) ($intent->metadata['order_id'] ?? 0);
        if (! $orderId) {
            throw new \RuntimeException('Order id missing in intent metadata');
        }

        DB::beginTransaction();
        try {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            $txn = Transaction::where('gateway', 'stripe')
                ->where('gateway_payment_id', $intent->id)
                ->lockForUpdate()
                ->first();

            if (! $txn) {
                $txn = Transaction::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'gateway' => 'stripe',
                    'gateway_payment_id' => $intent->id,
                    'amount' => (int) $intent->amount_received,
                    'currency' => strtoupper($intent->currency ?? 'USD'),
                    'status' => 'pending',
                    'payload' => ['intent' => $intent->toArray()],
                ]);
            }

            if ($intent->status !== 'succeeded') {
                throw new \RuntimeException('PaymentIntent not succeeded');
            }

            // Idempotency check: if already paid, skip
            if ($order->status === 'paid') {
                Log::info('Order already marked as paid, skipping duplicate confirmation', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId
                ]);
                DB::commit();
                return ['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number];
            }

            $txn->update([
                'status' => 'paid',
                'processed_via' => 'controller',
                'payload' => array_merge($txn->payload ?? [], ['confirm_without_webhook' => true]),
            ]);

            $order->update(['status' => 'paid']);

            // Update Payment status
            $methodId = optional(PaymentMethod::where('name', 'stripe')->first())->id;
            if ($methodId) {
                Payment::where('order_id', $order->id)
                    ->where('payment_method_id', $methodId)
                    ->update(['status' => 'paid']);
            } else {
                Payment::where('order_id', $order->id)->update(['status' => 'paid']);
            }

            // Clear cart
            if ($order->user_id && ($cart = Cart::where('user_id', $order->user_id)->first())) {
                $cart->items()->delete();
                $cart->update(['discount_code' => null, 'discount_amount' => 0]);
            }

            // Queue order confirmation email with logging
            Log::info('Stripe confirmAndMarkPaid: Dispatching order confirmation email job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_email' => $order->user->email ?? 'N/A'
            ]);
            dispatch(new \App\Jobs\SendOrderConfirmationEmail($order));

            DB::commit();
            return ['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Stripe payment confirmation failed: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}