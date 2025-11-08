<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    public function initiate(Request $request, RazorpayPaymentService $service)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $order = Order::whereIn('id', $validated['order_ids'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $paymentMethod = PaymentMethod::where('name', 'razorpay')->firstOrFail();

        DB::beginTransaction();
        try {
            Payment::firstOrCreate([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
            ], [
                'amount' => $order->total_amount,
                'currency' => 'INR',
                'status' => 'unpaid',
            ]);

            $r = $service->createOrder($order);

            DB::commit();

            return response()->json([
                'orderId' => $order->id,
                'razorpayOrderId' => $r['razorpay_order_id'],
                'amount' => $r['amount'],
                'currency' => $r['currency'],
                'key' => $r['key'],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Razorpay initiate failed', [
                'order_id' => $order->id ?? null,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Unable to initiate Razorpay payment: ' . $e->getMessage(),
            ], 422);
        }
    }

    // Note: Webhooks are disabled for Razorpay in this project as per requirements

    /**
     * Demo/test-mode confirmation without webhook.
     * Frontend posts razorpay_order_id and razorpay_payment_id after success.
     */
    public function confirm(Request $request, RazorpayPaymentService $service)
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
        ]);

        try {
            $result = $service->captureAndMarkPaid(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and captured',
                'order_id' => is_array($result) ? ($result['order_id'] ?? null) : $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay payment confirmation failed: ' . $e->getMessage(), [
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to confirm Razorpay payment: ' . $e->getMessage(),
            ], 422);
        }
    }
}


