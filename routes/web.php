<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use \App\Http\Controllers\Auth\PortalLoginController;
use \App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserAddressController;

use Modules\Products\Http\Controllers\StorefrontProductsController;
use Modules\Products\Http\Controllers\CategoryController;

Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/shop', [StorefrontProductsController::class, 'shop'])->name('shop');
Route::get('/shop/{id}', [StorefrontProductsController::class, 'show'])->name('shop.details');
Route::get('/categories', [CategoryController::class, 'storefrontIndex'])->name('categories.index');
Route::get('/categories/{id}', [CategoryController::class, 'storefrontShow'])->name('categories.show');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart/data', [CartController::class, 'getCartData'])->name('cart.data');
Route::patch('/cart/{productId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{productId}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/discount/apply', [CartController::class, 'applyDiscount'])->name('cart.discount.apply');
Route::get('/cart/discount/eligible-items', [CartController::class, 'getEligibleItems'])->name('cart.discount.eligible-items');
Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])->name('cart.discount.remove');
Route::get('/search', [StorefrontProductsController::class, 'search'])->name('products.search');
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/cancel-pending', [CheckoutController::class, 'cancelPending'])->name('checkout.cancel');

    // Address management routes
    Route::resource('addresses', UserAddressController::class);
    Route::post('/addresses/{address}/set-default', [UserAddressController::class, 'setDefault'])->name('addresses.set-default');

    // My Orders page (replaces success/failure redirects with SweetAlerts)
    Route::get('/myorder', function () {
        $orders = \App\Models\Order::with(['orderItems.product', 'orderItems.provider', 'paymentMethod'])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->get();
        return view('orders.myorder', compact('orders'));
    })->name('orders.myorder');

    // Product Rating routes (user only)
    Route::prefix('ratings')->as('ratings.')->group(function () {
        Route::get('/orders/{order}/eligible-products', [\App\Http\Controllers\ProductRatingController::class, 'getEligibleProducts'])->name('eligible-products');
        Route::post('/submit-batch', [\App\Http\Controllers\ProductRatingController::class, 'submitBatch'])->name('submit-batch');
        Route::get('/orders/{order}/products/{product}', [\App\Http\Controllers\ProductRatingController::class, 'getUserRating'])->name('user-rating');
        Route::post('/{id}/update', [\App\Http\Controllers\ProductRatingController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\ProductRatingController::class, 'destroy'])->name('destroy');
    });
});

// Provide a common dashboard route for auth flows/tests; redirect by role
Route::get('/dashboard', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user();
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole('provider')) {
        return redirect()->route('provider.dashboard');
    }
    return redirect()->route('home');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Separate login portals
Route::middleware('guest')->group(function () {
    // User portal
    Route::get('/login', [PortalLoginController::class, 'showUserLogin'])->name('login');
    Route::post('/login', [PortalLoginController::class, 'userLogin'])->name('login.submit');
    Route::post('/login/ajax', [PortalLoginController::class, 'ajaxUserLogin'])->name('login.ajax');

    // Admin/Provider portal
    Route::get('/admin/login', [PortalLoginController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [PortalLoginController::class, 'adminLogin'])->name('admin.login.submit');
});

// OTP verification routes (guest flow using session 'pending_user_id')
Route::get('/verify-otp', [EmailOtpController::class, 'show'])->name('verification.otp.notice');
Route::get('/verify-otp/send', [EmailOtpController::class, 'send'])->name('verification.otp.send');
Route::post('/verify-otp', [EmailOtpController::class, 'verify'])->name('verification.otp.verify');

// Guest verification resend routes
Route::get('/resend-verification', function () {
    return view('auth.resend-verification');
})->name('verification.resend');

Route::post('/resend-verification', function (\Illuminate\Http\Request $request) {
    // Rate limiting: 1 request per minute per email
    $key = 'resend-verification:' . $request->email;
    if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 1)) {
        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
        return back()->withErrors(['email' => "Please wait {$seconds} seconds before requesting another verification email."]);
    }

    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if ($user && is_null($user->email_verified_at)) {
        // Check for existing unexpired OTP first
        $existingOtp = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        try {
            if ($existingOtp) {
                // Reuse existing OTP
                $verifyUrl = route('verification.link', ['token' => $existingOtp->link_token]);
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerifyOtpMail($existingOtp->code, $verifyUrl));
            } else {
                // Create new OTP
                $otp = \App\Models\EmailOtp::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                    'link_token' => \Illuminate\Support\Str::random(64),
                    'expires_at' => now()->addMinutes(15),
                    'used' => false
                ]);

                // Send email with verification link
                $verifyUrl = route('verification.link', ['token' => $otp->link_token]);
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerifyOtpMail($otp->code, $verifyUrl));
            }

            // Hit rate limiter
            \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

            return redirect()->route('verification.resend')->with('status', 'A new verification link has been sent to your email address.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Failed to send verification email. Please try again.']);
        }
    } else {
        return back()->withErrors(['email' => 'This email is already verified or does not exist.']);
    }
})->name('verification.resend.submit');

// Link-based verification
Route::get('/verify-email/link/{token}', function (string $token) {
    $otp = \App\Models\EmailOtp::where('link_token', $token)->where('used', false)->first();
    if (!$otp || ($otp->expires_at && $otp->expires_at->isPast())) {
        abort(403, 'Invalid or expired verification link.');
    }
    $user = \App\Models\User::where('id', $otp->user_id)->where('email', $otp->email)->first();
    if (!$user) {
        abort(404);
    }
    // Mark email as verified
    if (method_exists($user, 'markEmailAsVerified')) {
        $user->markEmailAsVerified();
    }

    // Fire event for email verification
    event(new \App\Events\UserEmailVerified($user));

    // Auto-approve regular users after email verification
    if ($user->hasRole('user')) {
        $user->account_verified_at = now();
        $user->status = 'verified';
        $user->save();
    }

    $otp->used = true;
    $otp->save();
    return redirect()->route('login')->with('status', 'Email verified. Please login.');
})->name('verification.link');


