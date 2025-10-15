<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Cart;
use App\Mail\OrderConfirmationMail;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                        'payload' => array_merge($txn->payload ?? [], ['event' => $type, 'data' => $data->toArray()]),
                    ]);
                    $order->update(['status' => 'paid']);

                    // Mark payment as paid if present
                    Payment::where('order_id', $order->id)
                        ->where('gateway', 'stripe')
                        ->orWhereHas('method', function ($q) {
                            $q->where('name', 'stripe');
                        })
                        ->update(['status' => 'paid']);

                    // Clear cart for the user (if any)
                    if ($order->user_id) {
                        if ($cart = Cart::where('user_id', $order->user_id)->first()) {
                            $cart->items()->delete();
                            $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                        }
                    }

                    // Send confirmation email
                    try {
                        \Illuminate\Support\Facades\Mail::to(optional($order->user)->email)->send(new OrderConfirmationMail($order));
                    } catch (\Throwable $e) {
                        // swallow mail errors to not break webhook
                    }
                } else {
                    $txn->update([
                        'status' => 'failed',
                        'error_code' => $data->last_payment_error->code ?? null,
                        'error_message' => $data->last_payment_error->message ?? null,
                        'payload' => array_merge($txn->payload ?? [], ['event' => $type, 'data' => $data->toArray()]),
                    ]);
                    $order->update(['status' => 'failed']);

                    // Mark payment as failed
                    Payment::where('order_id', $order->id)
                        ->where(function ($q) {
                            $q->where('gateway', 'stripe')
                              ->orWhereHas('method', function ($q2) { $q2->where('name', 'stripe'); });
                        })
                        ->update(['status' => 'failed']);
                }
            });
        }
    }
}


