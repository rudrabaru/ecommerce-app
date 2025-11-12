<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Cart;
use App\Mail\OrderConfirmationMail;
use App\Models\Transaction;
use App\Services\Checkout\OrderPlacementService;
use App\Services\Checkout\CheckoutSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    private readonly StripeClient $client;

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
            'status' => 'unpaid',
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
                        'currency' => strtoupper((string) $data->currency),
                        'status' => 'unpaid',
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
                    if ($order->user_id && $cart = Cart::where('user_id', $order->user_id)->first()) {
                        $cart->items()->delete();
                        $cart->update(['discount_code' => null, 'discount_amount' => 0]);
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
                        'status' => 'unpaid', // Keep as unpaid on failure, not 'failed'
                        'processed_via' => 'webhook',
                        'error_code' => $data->last_payment_error->code ?? null,
                        'error_message' => $data->last_payment_error->message ?? null,
                        'payload' => array_merge($txn->payload ?? [], ['event' => $type, 'data' => $data->toArray()]),
                    ]);
                    // Don't update order status to 'failed' - keep it as is
                    // Payment remains unpaid

                    // Keep payment as unpaid on failure
                    Payment::where('order_id', $order->id)
                        ->whereHas('paymentMethod', function ($q) { $q->where('name', 'stripe'); })
                        ->update(['status' => 'unpaid']);

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
    public function confirmAndMarkPaid(string $paymentIntentId, ?string $checkoutSessionId = null): array
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId);

        $sessionId = $checkoutSessionId ?: ($intent->metadata['checkout_session_id'] ?? null);

        if ($sessionId) {
            return $this->finalizeIntentForSession($intent, $sessionId);
        }

        $orderId = (int) ($intent->metadata['order_id'] ?? 0);
        if ($orderId === 0) {
            throw new \RuntimeException('Checkout session or order metadata missing for payment intent');
        }

        return $this->finalizeIntentForExistingOrder($intent, $orderId, $paymentIntentId);
    }

    protected function finalizeIntentForExistingOrder($intent, int $orderId, string $paymentIntentId): array
    {
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
                    'status' => 'unpaid',
                    'payload' => ['intent' => $intent->toArray()],
                ]);
            }

            if ($intent->status !== 'succeeded') {
                throw new \RuntimeException('PaymentIntent not succeeded');
            }

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

            $methodId = optional(PaymentMethod::where('name', 'stripe')->first())->id;
            if ($methodId) {
                Payment::where('order_id', $order->id)
                    ->where('payment_method_id', $methodId)
                    ->update(['status' => 'paid']);
            } else {
                Payment::where('order_id', $order->id)->update(['status' => 'paid']);
            }

            if ($order->user_id && ($cart = Cart::where('user_id', $order->user_id)->first())) {
                $cart->items()->delete();
                $cart->update(['discount_code' => null, 'discount_amount' => 0]);
            }

            Log::info('Stripe confirmAndMarkPaid: Dispatching order confirmation email job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_email' => $order->user->email ?? 'N/A'
            ]);
            dispatch(new \App\Jobs\SendOrderConfirmationEmail($order));

            DB::commit();
            return ['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number];
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Stripe payment confirmation failed: ' . $throwable->getMessage(), [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId ?? null,
                'error' => $throwable->getMessage()
            ]);
            throw $throwable;
        }
    }

    protected function finalizeIntentForSession($intent, string $sessionId): array
    {
        $payload = CheckoutSessionService::retrieve($sessionId);

        $txn = Transaction::where('gateway', 'stripe')
            ->where('gateway_payment_id', $intent->id)
            ->first();

        if (! $payload) {
            if ($txn && $txn->order_id) {
                $order = Order::find($txn->order_id);
                if ($order) {
                    return ['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number];
                }
            }

            throw new \RuntimeException('Checkout session has expired. Please restart the checkout process.');
        }

        if ($intent->status !== 'succeeded') {
            if ($txn) {
                $txn->update([
                    'status' => 'failed',
                    'payload' => array_merge($txn->payload ?? [], [
                        'intent' => $intent->toArray(),
                        'error_message' => $intent->last_payment_error->message ?? 'Payment failed',
                    ]),
                ]);
            }

            OrderPlacementService::recordFailedPayment($payload, [
                'payment_gateway_transaction_id' => $intent->id,
                'gateway_response' => $intent->toArray(),
            ]);

            throw new \RuntimeException('PaymentIntent not succeeded');
        }

        DB::beginTransaction();
        try {
            $order = OrderPlacementService::create($payload, [
                'payment_status' => 'paid',
                'order_status' => 'pending',
                'clear_cart' => ($payload['cart_type'] ?? 'user') === 'user',
                'clear_session_cart' => ($payload['cart_type'] ?? 'user') === 'session',
                'send_email' => true,
                'payment_gateway_transaction_id' => $intent->id,
                'gateway_response' => $intent->toArray(),
            ]);

            $txnForUpdate = Transaction::where('gateway', 'stripe')
                ->where('gateway_payment_id', $intent->id)
                ->lockForUpdate()
                ->first();

            if ($txnForUpdate) {
                $txnForUpdate->update([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'status' => 'paid',
                    'amount' => (int) $intent->amount_received,
                    'currency' => strtoupper($intent->currency ?? 'USD'),
                    'processed_via' => 'controller',
                    'payload' => array_merge($txnForUpdate->payload ?? [], [
                        'checkout_session_id' => $sessionId,
                        'confirm_without_webhook' => true,
                        'intent' => $intent->toArray(),
                    ]),
                ]);
            } else {
                $txnForUpdate = Transaction::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'gateway' => 'stripe',
                    'gateway_payment_id' => $intent->id,
                    'amount' => (int) $intent->amount_received,
                    'currency' => strtoupper($intent->currency ?? 'USD'),
                    'status' => 'paid',
                    'processed_via' => 'controller',
                    'payload' => [
                        'checkout_session_id' => $sessionId,
                        'intent' => $intent->toArray(),
                    ],
                ]);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Stripe payment confirmation failed for checkout session: ' . $throwable->getMessage(), [
                'payment_intent_id' => $intent->id,
                'checkout_session_id' => $sessionId,
                'error' => $throwable->getMessage()
            ]);
            throw $throwable;
        }

        CheckoutSessionService::forget($sessionId);

        return ['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number];
    }

    public function refundPayment(string $paymentIntentId, ?int $amountMinor = null): string
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId);
        $chargeId = $intent->latest_charge ?? ($intent->charges->data[0]->id ?? null);

        if (!$chargeId) {
            throw new \RuntimeException('Unable to determine Stripe charge for refund.');
        }

        // Check if charge has already been refunded
        try {
            $charge = $this->client->charges->retrieve($chargeId);
            if ($charge->refunded) {
                // Find existing refund ID
                $refunds = $this->client->refunds->all(['charge' => $chargeId]);
                if ($refunds->data && count($refunds->data) > 0) {
                    Log::info('Stripe charge already refunded, returning existing refund ID', [
                        'payment_intent_id' => $paymentIntentId,
                        'charge_id' => $chargeId,
                        'refund_id' => $refunds->data[0]->id,
                    ]);
                    return $refunds->data[0]->id;
                }
                throw new \RuntimeException('Charge has already been refunded, but refund ID could not be retrieved.');
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // If charge retrieval fails, continue with refund attempt
            Log::warning('Could not check charge refund status', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
        }

        $params = [
            'charge' => $chargeId,
        ];

        if ($amountMinor && $amountMinor > 0) {
            $params['amount'] = $amountMinor;
        }

        try {
            $refund = $this->client->refunds->create($params);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Handle "already refunded" error gracefully
            if (stripos($e->getMessage(), 'already been refunded') !== false) {
                // Try to get existing refund
                $refunds = $this->client->refunds->all(['charge' => $chargeId]);
                if ($refunds->data && count($refunds->data) > 0) {
                    Log::info('Stripe refund already exists, returning existing refund ID', [
                        'payment_intent_id' => $paymentIntentId,
                        'charge_id' => $chargeId,
                        'refund_id' => $refunds->data[0]->id,
                    ]);
                    return $refunds->data[0]->id;
                }
            }
            throw $e;
        }

        Log::info('Stripe refund created', [
            'payment_intent_id' => $paymentIntentId,
            'charge_id' => $chargeId,
            'refund_id' => $refund->id,
            'amount' => $amountMinor,
        ]);

        return $refund->id;
    }

    /**
     * Create payment intent from checkout session data
     * This method creates the order first, then creates the payment intent
     */
    public function createIntentFromCheckoutSession(string $checkoutSessionId, array $sessionPayload): array
    {
        $amountBase = (float) ($sessionPayload['total_amount'] ?? 0);
        if ($amountBase <= 0) {
            throw new \RuntimeException('Invalid checkout amount for Stripe payment intent.');
        }

        $currency = strtolower($sessionPayload['currency'] ?? 'usd');
        $amountMinor = (int) round($amountBase * 100);

        $metadata = [
            'checkout_session_id' => $checkoutSessionId,
            'user_id' => (string) ($sessionPayload['user_id'] ?? ''),
        ];

        $intent = $this->client->paymentIntents->create([
            'amount' => $amountMinor,
            'currency' => $currency,
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }
}