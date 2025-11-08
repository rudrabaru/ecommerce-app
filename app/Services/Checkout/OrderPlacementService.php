<?php

namespace App\Services\Checkout;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\DiscountService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderPlacementService
{
    public static function create(array $data, array $options = []): Order
    {
        return DB::transaction(function () use ($data, $options) {
            $paymentStatus = Arr::get($options, 'payment_status', 'unpaid');
            $paymentMethodId = Arr::get($data, 'payment_method_id');
            $paymentMethodName = Arr::get($data, 'payment_method_name');
            $totalAmount = (float) Arr::get($data, 'total_amount', 0);
            $currency = Arr::get($data, 'currency', 'USD');
            $userId = Arr::get($data, 'user_id');

            $order = Order::create([
                'user_id' => $userId,
                'provider_ids' => Arr::get($data, 'provider_ids', []),
                'total_amount' => $totalAmount,
                'order_status' => Arr::get($options, 'order_status', 'pending'),
                'shipping_address' => Arr::get($data, 'shipping_address'),
                'shipping_address_id' => Arr::get($data, 'shipping_address_id'),
                'payment_method_id' => $paymentMethodId,
                'notes' => Arr::get($data, 'notes'),
                'discount_code' => Arr::get($data, 'discount_code'),
                'discount_amount' => Arr::get($data, 'discount_amount', 0),
            ]);

            foreach (Arr::get($data, 'order_items', []) as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => Arr::get($item, 'product_id'),
                    'provider_id' => Arr::get($item, 'provider_id'),
                    'quantity' => Arr::get($item, 'quantity'),
                    'unit_price' => Arr::get($item, 'unit_price'),
                    'line_total' => Arr::get($item, 'line_total'),
                    'line_discount' => Arr::get($item, 'line_discount', 0),
                    'total' => Arr::get($item, 'total'),
                    'order_status' => OrderItem::STATUS_PENDING,
                ]);
            }

            $order->recalculateOrderStatus();

            if ($paymentStatus) {
                $paymentData = [
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethodId,
                    'amount' => $totalAmount,
                    'currency' => $currency,
                    'status' => $paymentStatus,
                    'gateway_transaction_id' => Arr::get($options, 'payment_gateway_transaction_id'),
                    'gateway_response' => Arr::get($options, 'gateway_response'),
                ];

                if ($paymentStatus === 'paid') {
                    $paymentData['paid_at'] = now();
                }

                Payment::create($paymentData);
            }

            if ($code = Arr::get($data, 'discount_code')) {
                try {
                    $discount = \App\Models\DiscountCode::whereRaw('upper(code) = ?', [strtoupper($code)])->first();
                    if ($discount) {
                        (new DiscountService())->incrementUsage($discount);
                    }
                } catch (\Throwable $_) {
                    // ignore discount update failure
                }
            }

            if (Arr::get($options, 'clear_cart', false) && $userId) {
                if ($cart = Cart::where('user_id', $userId)->first()) {
                    $cart->items()->delete();
                    $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                }
            }

            if (Arr::get($options, 'clear_session_cart', false)) {
                session()->forget(['cart', 'cart_discount_code', 'cart_discount']);
            }

            if (Arr::get($options, 'send_email', false) && $order->user && $order->user->email) {
                try {
                    dispatch(new SendOrderConfirmationEmail($order));
                } catch (\Throwable $e) {
                    try {
                        Mail::to($order->user->email)->send(new \App\Mail\OrderConfirmationMail($order));
                    } catch (\Throwable $_) {
                        // ignore email failure
                    }
                }
            }

            return $order;
        });
    }

    public static function recordFailedPayment(array $data, array $options = []): void
    {
        $paymentMethodId = Arr::get($data, 'payment_method_id');
        $paymentMethodName = Arr::get($data, 'payment_method_name');
        $totalAmount = (float) Arr::get($data, 'total_amount', 0);
        $currency = Arr::get($data, 'currency', 'USD');

        if (!$paymentMethodId && $paymentMethodName) {
            $paymentMethodId = optional(PaymentMethod::where('name', $paymentMethodName)->first())->id;
        }

        Payment::create([
            'order_id' => null,
            'payment_method_id' => $paymentMethodId,
            'amount' => $totalAmount,
            'currency' => $currency,
            'status' => 'failed',
            'gateway_transaction_id' => Arr::get($options, 'payment_gateway_transaction_id'),
            'gateway_response' => Arr::get($options, 'gateway_response'),
        ]);
    }
}

