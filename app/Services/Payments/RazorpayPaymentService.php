<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Cart;
use App\Mail\OrderConfirmationMail;
use App\Services\Checkout\OrderPlacementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api as RazorpayClient;

class RazorpayPaymentService
{
    private readonly RazorpayClient $client;

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
            $amountBase *= 83.0; // demo conversion
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
            'status' => 'unpaid',
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
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Razorpay captureAndMarkPaid failed: ' . $throwable->getMessage(), [
                'razorpay_order_id' => $razorpayOrderId,
                'payment_id' => $paymentId,
                'trace' => $throwable->getTraceAsString()
            ]);
            throw $throwable;
        }
    }

    public function refundPayment(string $paymentId, ?int $amountMinor = null): string
    {
        $params = [];
        if ($amountMinor && $amountMinor > 0) {
            $params['amount'] = $amountMinor;
        }

        try {
            $payment = $this->client->payment->fetch($paymentId);
            
            // Convert payment object to array for easier access
            $paymentArray = is_array($payment) ? $payment : $payment->toArray();
            $paymentStatus = $paymentArray['status'] ?? ($payment->status ?? 'unknown');
            
            // Check if payment is captured
            if ($paymentStatus !== 'captured') {
                // Try to capture if authorized
                if ($paymentStatus === 'authorized') {
                    try {
                        // Get amount and currency from transaction (more reliable than payment object)
                        $transaction = Transaction::where('gateway', 'razorpay')
                            ->where('gateway_payment_id', $paymentId)
                            ->latest('id')
                            ->first();
                        
                        if ($transaction) {
                            $paymentAmount = $transaction->amount;
                            $paymentCurrency = $transaction->currency ?? 'INR';
                        } else {
                            // Fallback to payment object
                            $paymentAmount = $paymentArray['amount'] ?? ($payment->amount ?? null);
                            $paymentCurrency = $paymentArray['currency'] ?? ($payment->currency ?? 'INR');
                            
                            if (!$paymentAmount) {
                                throw new \RuntimeException('Unable to determine payment amount for capture.');
                            }
                        }
                        
                        // Ensure amount is in minor units (integer)
                        if (is_float($paymentAmount) || is_string($paymentAmount)) {
                            $paymentAmount = (int) round((float) $paymentAmount);
                        }
                        
                        // Use the same pattern as captureAndMarkPaid - use client->payment->capture with payment ID
                        $currencyForCapture = $transaction ? $transaction->currency : $paymentCurrency;
                        
                        // Capture using the same method as captureAndMarkPaid (line 118)
                        $this->client->payment->capture($paymentId, $paymentAmount, ['currency' => $currencyForCapture]);
                        
                        Log::info('Razorpay payment captured before refund', [
                            'payment_id' => $paymentId,
                            'amount' => $paymentAmount,
                            'currency' => $currencyForCapture
                        ]);
                        
                        // Refetch payment after capture to get updated status
                        $payment = $this->client->payment->fetch($paymentId);
                        $paymentArray = is_array($payment) ? $payment : $payment->toArray();
                    } catch (\Exception $e) {
                        Log::warning('Failed to capture Razorpay payment before refund', [
                            'payment_id' => $paymentId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw new \RuntimeException('Payment must be captured before refund. Status: ' . $paymentStatus . '. Error: ' . $e->getMessage());
                    }
                } else {
                    throw new \RuntimeException('Payment status must be captured for refund. Current status: ' . $paymentStatus);
                }
            }

            $refund = $payment->refund($params);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $errorMessage = $e->getMessage();
            if (stripos($errorMessage, 'already been refunded') !== false || stripos($errorMessage, 'refunded') !== false) {
                // Try to get existing refunds
                try {
                    $refunds = $this->client->refund->all(['payment_id' => $paymentId]);
                    if ($refunds->items && count($refunds->items) > 0) {
                        $refundId = is_array($refunds->items[0]) ? ($refunds->items[0]['id'] ?? null) : ($refunds->items[0]->id ?? null);
                        if ($refundId) {
                            Log::info('Razorpay payment already refunded, returning existing refund ID', [
                                'payment_id' => $paymentId,
                                'refund_id' => $refundId,
                            ]);
                            return $refundId;
                        }
                    }
                } catch (\Exception $e2) {
                    Log::warning('Could not retrieve existing Razorpay refund', [
                        'payment_id' => $paymentId,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
            throw new \RuntimeException($errorMessage);
        }

        // Handle refund response - it might be an array or object
        $refundArray = is_array($refund) ? $refund : (method_exists($refund, 'toArray') ? $refund->toArray() : []);
        $refundId = $refundArray['id'] ?? ($refund->id ?? null);
        
        // If still not found, check nested structure
        if (!$refundId && isset($refundArray['refund'])) {
            $refundId = is_array($refundArray['refund']) ? ($refundArray['refund']['id'] ?? null) : ($refundArray['refund']->id ?? null);
        }

        if (!$refundId) {
            Log::warning('Razorpay refund ID not found in response', [
                'payment_id' => $paymentId,
                'refund_response' => $refundArray
            ]);
            throw new \RuntimeException('Refund was processed but refund ID could not be retrieved from Razorpay response.');
        }

        Log::info('Razorpay refund processed', [
            'payment_id' => $paymentId,
            'refund_id' => $refundId,
            'amount' => $amountMinor,
        ]);

        return (string) $refundId;
    }

    /**
     * Create Razorpay order from checkout session data
     * This method creates the order first, then creates the Razorpay order
     */
    public function createOrderFromCheckoutSession(string $checkoutSessionId, array $sessionPayload): array
    {
        // Create the order from session payload
        $order = OrderPlacementService::create($sessionPayload, [
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'clear_cart' => ($sessionPayload['cart_type'] ?? 'user') === 'user',
            'clear_session_cart' => ($sessionPayload['cart_type'] ?? 'user') === 'session',
            'send_email' => false, // Will send after payment confirmation
        ]);

        // Create Razorpay order for the order
        $razorpayOrderData = $this->createOrder($order);

        // Store checkout session ID in transaction payload for reference
        $txn = Transaction::where('gateway', 'razorpay')
            ->where('gateway_order_id', $razorpayOrderData['razorpay_order_id'])
            ->first();
        
        if ($txn) {
            $txn->update([
                'payload' => array_merge($txn->payload ?? [], ['checkout_session_id' => $checkoutSessionId])
            ]);
        }

        return $razorpayOrderData;
    }

    // Webhook handling removed by requirement; confirmation is handled via confirm endpoint only
}