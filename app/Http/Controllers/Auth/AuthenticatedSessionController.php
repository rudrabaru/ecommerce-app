<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\EmailOtp;
use App\Mail\VerifyOtpMail;
use Illuminate\Support\Facades\Mail;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Merge guest cart into user's database cart BEFORE session regeneration
        $this->mergeGuestCart($user);

        // Restrict login for regular users unless email is verified
        if ($user->hasRole('user') && is_null($user->email_verified_at)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Send verification email for unverified users (same as registration flow)
            $this->sendVerificationEmail($user);
            
            return redirect()->route('login')->withErrors(['email' => 'Please verify your email before logging in.']);
        }

        $request->session()->regenerate();

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->hasRole('provider')) {
            return redirect()->intended(route('provider.dashboard'));
        }
        if ($user->hasRole('user')) {
            return redirect()->intended(route('home'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function mergeGuestCart($user)
    {
        $guestCart = session('cart', []);
        
        // Debug logging
        \Log::info('Cart merge attempt', [
            'user_id' => $user->id,
            'guest_cart_count' => count($guestCart),
            'guest_cart' => $guestCart
        ]);
        
        if (empty($guestCart)) {
            \Log::info('No guest cart items to merge');
            return;
        }

        // Get or create user's cart
        $userCart = \App\Models\Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart as $productId => $item) {
            $existingItem = $userCart->items()->where('product_id', $productId)->first();
            
            if ($existingItem) {
                // If item exists, add quantities
                $existingItem->quantity += $item['quantity'];
                $existingItem->save();
                \Log::info('Updated existing cart item', [
                    'product_id' => $productId,
                    'new_quantity' => $existingItem->quantity
                ]);
            } else {
                // Create new cart item
                $cartItem = $userCart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
                \Log::info('Created new cart item', [
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);
            }
        }

        // Merge discount if exists
        if (session()->has('cart_discount_code')) {
            $userCart->update([
                'discount_code' => session('cart_discount_code'),
                'discount_amount' => session('cart_discount', 0)
            ]);
            \Log::info('Merged discount code', [
                'discount_code' => session('cart_discount_code'),
                'discount_amount' => session('cart_discount', 0)
            ]);
        }

        // Clear guest cart
        session()->forget(['cart', 'cart_discount_code', 'cart_discount']);
        \Log::info('Guest cart cleared after merge');
    }

    /**
     * Send verification email to unverified user (same as registration flow)
     */
    private function sendVerificationEmail($user)
    {
        try {
            // Generate OTP and link token
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $linkToken = bin2hex(random_bytes(20));
            
            // Create or update EmailOtp record
            EmailOtp::updateOrCreate(
                ['user_id' => $user->id, 'email' => $user->email, 'used' => false],
                [
                    'code' => $code, 
                    'link_token' => $linkToken, 
                    'expires_at' => now()->addMinutes(15)
                ]
            );
            
            // Generate verification URL
            $verifyUrl = url('/verify-email/link/'.$linkToken);
            
            // Send email with OTP and verification link
            Mail::to($user->email)->send(new VerifyOtpMail($code, $verifyUrl));
            
            \Log::info('Verification email sent to unverified user', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email to unverified user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
