<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PortalLoginController extends Controller
{
    public function showUserLogin()
    {
        return view('auth.login', ['portal' => 'user']);
    }

    public function showAdminLogin()
    {
        return view('auth.login', ['portal' => 'admin']);
    }

    public function userLogin(Request $request)
    {
        $this->validateLogin($request);
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        $user = Auth::user();
        
        // Merge guest cart into user's database cart BEFORE session regeneration
        $this->mergeGuestCart($user);

        $request->session()->regenerate();

        if ($user->hasRole('admin') || $user->hasRole('provider')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Unauthorized: Only users can log in here.']);
        }

        return redirect()->intended(route('home'));
    }

    public function adminLogin(Request $request)
    {
        $this->validateLogin($request);
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->hasRole('user')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Unauthorized: Only admins/providers can log in here.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    private function validateLogin(Request $request): void
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower($request->input('email')).'|'.$request->ip();
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }
        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey($request))]),
        ]);
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
}



