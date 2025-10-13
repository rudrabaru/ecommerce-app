<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function initiate(Request $request, StripePaymentService $service)
    {
        $user = $request->user();

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        // For simplicity, handle one order per payment
        $order = Order::whereIn('id', $validated['order_ids'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $paymentMethod = PaymentMethod::where('name', 'stripe')->firstOrFail();

        DB::beginTransaction();
        try {
            // Ensure payment row exists
            Payment::firstOrCreate([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
            ], [
                'amount' => $order->total_amount,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            $pi = $service->createPaymentIntent($order);

            DB::commit();

            return response()->json([
                'orderId' => $order->id,
                'clientSecret' => $pi['client_secret'],
                'publishableKey' => config('services.stripe.key'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Unable to initiate Stripe payment',
            ], 422);
        }
    }

    public function webhook(Request $request, StripePaymentService $service)
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');
        $service->handleWebhook($payload, (string) $sig);
        return response()->json(['received' => true]);
    }
}


