<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'checkout_session_id' => ['required', 'string'],
        ]);

        $sessionId = $validated['checkout_session_id'];
        $sessionPayload = CheckoutSessionService::retrieve($sessionId);

        if (! $sessionPayload) {
            return response()->json([
                'message' => 'Checkout session expired. Please restart the checkout process.',
            ], 422);
        }

        if ((int) ($sessionPayload['user_id'] ?? 0) !== (int) $user->id) {
            return response()->json([
                'message' => 'Checkout session does not belong to the authenticated user.',
            ], 403);
        }

        try {
            $r = $service->createOrderFromCheckoutSession($sessionId, $sessionPayload);

            return response()->json([
                'razorpayOrderId' => $r['razorpay_order_id'],
                'amount' => $r['amount'],
                'currency' => $r['currency'],
                'key' => $r['key'],
                'checkoutSessionId' => $sessionId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay initiate failed', [
                'checkout_session_id' => $sessionId,
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
            'checkout_session_id' => ['nullable', 'string'],
        ]);

        try {
            $result = $service->captureAndMarkPaid(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['checkout_session_id'] ?? null
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
                'checkout_session_id' => $validated['checkout_session_id'] ?? null,
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to confirm Razorpay payment: ' . $e->getMessage(),
            ], 422);
        }
    }
}


