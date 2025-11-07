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
use Illuminate\Support\Facades\Log;

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
                'status' => 'unpaid',
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
            Log::error('Stripe initiate failed', [
                'order_id' => $order->id ?? null,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Unable to initiate Stripe payment: ' . $e->getMessage(),
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

    /**
     * Demo/local confirmation endpoint without webhooks.
     * Frontend posts payment_intent_id after confirmCardPayment success.
     */
    public function confirm(Request $request, StripePaymentService $service)
    {
        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        try {
            $res = $service->confirmAndMarkPaid($validated['payment_intent_id']);
            return response()->json($res);
        } catch (\Throwable $e) {
            Log::error('Stripe payment confirm failed: ' . $e->getMessage(), ['pi' => $validated['payment_intent_id']]);
            return response()->json(['success' => false, 'message' => 'Unable to confirm Stripe payment'], 422);
        }
    }
}


