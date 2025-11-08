<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * Create or update a refund request for the given order and payment context.
     */
    public static function upsertRefundRequest(Order $order, array $attributes): RefundRequest
    {
        $payment = $attributes['payment'] ?? null;
        $paymentMethod = $attributes['payment_method'] ?? null;
        $amount = (float) ($attributes['amount'] ?? 0);
        $currency = $attributes['currency'] ?? 'USD';
        $providerId = $attributes['provider_id'] ?? null;
        $userId = $attributes['user_id'] ?? null;
        $orderItemId = $attributes['order_item_id'] ?? null;
        $status = $attributes['status'] ?? RefundRequest::STATUS_PENDING;
        $notes = $attributes['notes'] ?? null;
        $bankDetails = $attributes['bank_details'] ?? [];

        if (!$paymentMethod) {
            throw new \InvalidArgumentException('Payment method is required to create a refund request.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }

        // For full order returns (order_item_id is null), check if a full order refund already exists
        // For item-level returns, check if refund for this specific item exists
        $query = RefundRequest::where('order_id', $order->id)
            ->where('payment_method', $paymentMethod)
            ->when($payment, fn($q) => $q->where('payment_id', $payment->id))
            ->when($orderItemId, fn($q) => $q->where('order_item_id', $orderItemId))
            ->when($orderItemId === null, fn($q) => $q->whereNull('order_item_id')) // Full order return
            ->when($providerId, fn($q) => $q->where('provider_id', $providerId));

        $refundRequest = $query->first();

        if (! $refundRequest) {
            $refundRequest = new RefundRequest();
            $refundRequest->order_id = $order->id;
            $refundRequest->order_item_id = $orderItemId;
            $refundRequest->payment_method = $paymentMethod;
            $refundRequest->provider_id = $providerId;
            $refundRequest->payment_id = $payment?->id;
            $refundRequest->user_id = $userId ?? $order->user_id;
        }

        $refundRequest->amount = $amount;
        $refundRequest->currency = $currency;
        $refundRequest->status = $status;
        $refundRequest->notes = $notes;

        if (!empty($bankDetails)) {
            $refundRequest->account_holder_name = $bankDetails['account_holder_name'] ?? $refundRequest->account_holder_name;
            $refundRequest->bank_name = $bankDetails['bank_name'] ?? $refundRequest->bank_name;
            $refundRequest->account_number = $bankDetails['account_number'] ?? $refundRequest->account_number;
            $refundRequest->ifsc = $bankDetails['ifsc'] ?? $refundRequest->ifsc;
        }

        $refundRequest->save();

        return $refundRequest;
    }

    /**
     * Helper to determine the default currency from payment or order.
     */
    public static function resolveCurrency(Order $order, ?Payment $payment = null): string
    {
        if ($payment && !empty($payment->currency)) {
            return strtoupper($payment->currency);
        }

        if (!empty($order->currency)) {
            return strtoupper($order->currency);
        }

        return 'USD';
    }

    public static function processRefundRequest(RefundRequest $refundRequest): RefundRequest
    {
        // Allow retry for failed refunds
        if ($refundRequest->status === RefundRequest::STATUS_COMPLETED) {
            return $refundRequest;
        }

        // Ensure order is loaded
        if (!$refundRequest->relationLoaded('order')) {
            $refundRequest->load('order');
        }
        
        if (!$refundRequest->order) {
            throw new \RuntimeException('Order not found for refund request ID: ' . $refundRequest->id);
        }

        // Reset failed status to processing for retry
        if ($refundRequest->status === RefundRequest::STATUS_FAILED) {
            $refundRequest->status = RefundRequest::STATUS_PROCESSING;
            $refundRequest->save();
        } else {
            $refundRequest->status = RefundRequest::STATUS_PROCESSING;
            $refundRequest->save();
        }

        try {
            switch ($refundRequest->payment_method) {
                case 'cod':
                    $refundRequest->status = RefundRequest::STATUS_COMPLETED;
                    $refundRequest->gateway_refund_id = $refundRequest->gateway_refund_id ?: 'manual-' . uniqid();
                    $refundRequest->notes = trim(sprintf(
                        "%s\nManual refund approved on %s",
                        $refundRequest->notes ?? '',
                        now()->toDateTimeString()
                    ));
                    $refundRequest->save();
                    self::updatePaymentStatusAfterRefund($refundRequest);
                    break;

                case 'stripe':
                    $gatewayId = self::processStripeRefund($refundRequest);
                    $refundRequest->status = RefundRequest::STATUS_COMPLETED;
                    $refundRequest->gateway_refund_id = $gatewayId;
                    $refundRequest->save();
                    self::updatePaymentStatusAfterRefund($refundRequest);
                    break;

                case 'razorpay':
                    $gatewayId = self::processRazorpayRefund($refundRequest);
                    $refundRequest->status = RefundRequest::STATUS_COMPLETED;
                    $refundRequest->gateway_refund_id = $gatewayId;
                    $refundRequest->save();
                    self::updatePaymentStatusAfterRefund($refundRequest);
                    break;

                default:
                    throw new \RuntimeException('Unsupported payment method for refunds: ' . $refundRequest->payment_method);
            }

            return $refundRequest;
        } catch (\Throwable $throwable) {
            Log::error('Refund processing failed', [
                'refund_request_id' => $refundRequest->id,
                'order_id' => $refundRequest->order_id,
                'payment_method' => $refundRequest->payment_method,
                'error' => $throwable->getMessage(),
            ]);

            $refundRequest->status = RefundRequest::STATUS_FAILED;
            $refundRequest->notes = trim(($refundRequest->notes ? $refundRequest->notes . "\n" : '') . 'Refund failed: ' . $throwable->getMessage());
            $refundRequest->save();

            // Don't throw - allow admin to retry via UI
            return $refundRequest;
        }
    }

    protected static function processStripeRefund(RefundRequest $refundRequest): string
    {
        if (!$refundRequest->relationLoaded('order')) {
            $refundRequest->load('order');
        }
        
        $order = $refundRequest->order;
        if (!$order) {
            throw new \RuntimeException('Order not found for Stripe refund.');
        }
        
        $order->loadMissing('payment');
        
        $intentId = self::resolveStripePaymentIntent($order);
        if (!$intentId) {
            throw new \RuntimeException('Stripe payment intent not found for refund.');
        }

        $amountMinor = (int) round(((float) $refundRequest->amount) * 100);
        if ($amountMinor <= 0) {
            throw new \RuntimeException('Refund amount must be greater than zero.');
        }

        /** @var \App\Services\Payments\StripePaymentService $service */
        $service = app(\App\Services\Payments\StripePaymentService::class);
        return $service->refundPayment($intentId, $amountMinor);
    }

    protected static function resolveStripePaymentIntent(?Order $order): ?string
    {
        if (!$order) {
            return null;
        }

        $transaction = Transaction::where('order_id', $order->id)
            ->where('gateway', 'stripe')
            ->latest('id')
            ->first();

        if ($transaction && !empty($transaction->gateway_payment_id)) {
            return $transaction->gateway_payment_id;
        }

        $payloadIntent = $transaction ? Arr::get($transaction->payload, 'intent.id') : null;
        if ($payloadIntent) {
            return $payloadIntent;
        }

        $payment = $order->payment()->latest('id')->first();
        if ($payment && !empty($payment->gateway_transaction_id)) {
            return $payment->gateway_transaction_id;
        }

        return null;
    }

    protected static function processRazorpayRefund(RefundRequest $refundRequest): string
    {
        if (!$refundRequest->relationLoaded('order')) {
            $refundRequest->load('order');
        }
        
        $order = $refundRequest->order;
        if (!$order) {
            throw new \RuntimeException('Order not found for Razorpay refund.');
        }
        
        $order->loadMissing('payment');
        
        $paymentId = self::resolveRazorpayPaymentId($order);
        if (!$paymentId) {
            throw new \RuntimeException('Razorpay payment id not found for refund.');
        }

        // Convert to minor units (paise for INR, cents for USD)
        $amountMinor = (int) round(((float) $refundRequest->amount) * 100);
        if ($amountMinor <= 0) {
            throw new \RuntimeException('Refund amount must be greater than zero.');
        }

        /** @var \App\Services\Payments\RazorpayPaymentService $service */
        $service = app(\App\Services\Payments\RazorpayPaymentService::class);
        return $service->refundPayment($paymentId, $amountMinor);
    }

    protected static function resolveRazorpayPaymentId(?Order $order): ?string
    {
        if (!$order) {
            return null;
        }

        $transaction = Transaction::where('order_id', $order->id)
            ->where('gateway', 'razorpay')
            ->latest('id')
            ->first();

        if ($transaction && !empty($transaction->gateway_payment_id)) {
            return $transaction->gateway_payment_id;
        }

        $payloadPaymentId = $transaction ? Arr::get($transaction->payload, 'payment.id') : null;
        if ($payloadPaymentId) {
            return $payloadPaymentId;
        }

        $payment = $order->payment()->latest('id')->first();
        if ($payment && !empty($payment->gateway_transaction_id)) {
            return $payment->gateway_transaction_id;
        }

        return null;
    }

    protected static function updatePaymentStatusAfterRefund(RefundRequest $refundRequest): void
    {
        $order = $refundRequest->order;
        if (!$order) {
            return;
        }

        // Get payment from refund request or order
        $payment = $refundRequest->payment;
        if (! $payment) {
            $payment = $order->payment()->latest('id')->first();
        }

        if (! $payment) {
            return;
        }

        // Get all refund requests for this payment (by payment_id) or for this order if payment_id is null
        $paymentRefunds = RefundRequest::where('order_id', $order->id)
            ->where(function ($query) use ($payment) {
                $query->where('payment_id', $payment->id)
                      ->orWhereNull('payment_id');
            })
            ->get();

        if ($paymentRefunds->isEmpty()) {
            return;
        }

        $totalRefunded = $paymentRefunds->where('status', RefundRequest::STATUS_COMPLETED)->sum('amount');
        $paymentAmount = (float) ($payment->amount ?? 0);

        // Only mark as refunded if total refunded amount equals or exceeds payment amount
        // and all refund requests for this payment are completed (no pending/processing/failed)
        $hasPending = $paymentRefunds->whereIn('status', [RefundRequest::STATUS_PENDING, RefundRequest::STATUS_PROCESSING])->isNotEmpty();
        if (!$hasPending && $totalRefunded >= $paymentAmount - 0.01) {
            $payment->update(['status' => 'refunded']);
        }
    }
}

