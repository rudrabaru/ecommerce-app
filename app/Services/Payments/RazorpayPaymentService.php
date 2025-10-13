<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Transaction;
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
        $amountMinor = (int) round(((float) $order->total_amount) * 100);
        $currency = 'INR';

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
            } elseif (in_array($event, ['payment.failed'])) {
                $txn->update([
                    'gateway_payment_id' => $paymentId,
                    'status' => 'failed',
                    'payload' => array_merge($txn->payload ?? [], ['event' => $event, 'data' => $payload]),
                ]);
                $order->update(['status' => 'failed']);
            }
        });
    }
}


