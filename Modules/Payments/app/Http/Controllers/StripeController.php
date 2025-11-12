<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payments\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function initiate(Request $request, StripePaymentService $service)
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
            $intent = $service->createIntentFromCheckoutSession($sessionId, $sessionPayload);

            return response()->json([
                'checkoutSessionId' => $sessionId,
                'paymentIntentId' => $intent['payment_intent_id'],
                'clientSecret' => $intent['client_secret'],
                'publishableKey' => config('services.stripe.key'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe initiate failed', [
                'checkout_session_id' => $sessionId,
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
            'checkout_session_id' => ['nullable', 'string'],
        ]);

        try {
            $res = $service->confirmAndMarkPaid(
                $validated['payment_intent_id'],
                $validated['checkout_session_id'] ?? null
            );
            return response()->json($res);
        } catch (\Throwable $e) {
            Log::error('Stripe payment confirm failed: ' . $e->getMessage(), [
                'payment_intent_id' => $validated['payment_intent_id'],
                'checkout_session_id' => $validated['checkout_session_id'] ?? null,
            ]);
            return response()->json(['success' => false, 'message' => 'Unable to confirm Stripe payment'], 422);
        }
    }
}


