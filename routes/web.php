<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CartController;
Route::get('/', [\App\Http\Controllers\MainController::class, 'index'])->name('home');
Route::get('/checkout', [\App\Http\Controllers\MainController::class, 'checkout'])->name('checkout');
Route::get('/shopping-cart', [\App\Http\Controllers\MainController::class, 'cart'])->name('shopping.cart');
Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::get('/shop', [\Modules\Products\Http\Controllers\StorefrontProductsController::class, 'shop'])->name('shop');
Route::get('/shop/{id}', [\Modules\Products\Http\Controllers\StorefrontProductsController::class, 'show'])->name('shop.details');
// Category browsing (reuse existing module CategoryController)
Route::get('/categories', [\Modules\Products\Http\Controllers\StorefrontCategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{id}', [\Modules\Products\Http\Controllers\StorefrontCategoryController::class, 'show'])->name('categories.show');
// Friendly category routes
// Removed duplicate/legacy category routes in favor of categories.show
Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{productId}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{productId}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/discount/apply', [\App\Http\Controllers\CartController::class, 'applyDiscount'])->name('cart.discount.apply');
Route::delete('/cart/discount', [\App\Http\Controllers\CartController::class, 'removeDiscount'])->name('cart.discount.remove');
Route::get('/cart/dropdown', [\App\Http\Controllers\CartController::class, 'dropdown'])->name('cart.dropdown');
Route::get('/search', [\Modules\Products\Http\Controllers\StorefrontProductsController::class, 'search'])->name('products.search');
Route::middleware('auth')->group(function () {
    Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
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

// Remove default Breeze dashboard in favor of role-based dashboards

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Separate login portals
Route::middleware('guest')->group(function () {
    // User portal
    Route::get('/login', [\App\Http\Controllers\Auth\PortalLoginController::class, 'showUserLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\PortalLoginController::class, 'userLogin'])->name('login.submit');

    // Admin/Provider portal
    Route::get('/admin/login', [\App\Http\Controllers\Auth\PortalLoginController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [\App\Http\Controllers\Auth\PortalLoginController::class, 'adminLogin'])->name('admin.login.submit');
});

// Note: Module routes are loaded by their own service providers; no manual glob include here

// OTP verification routes (guest flow using session 'pending_user_id')
Route::get('/verify-otp', [\App\Http\Controllers\Auth\EmailOtpController::class, 'show'])->name('verification.otp.notice');
Route::get('/verify-otp/send', [\App\Http\Controllers\Auth\EmailOtpController::class, 'send'])->name('verification.otp.send');
Route::post('/verify-otp', [\App\Http\Controllers\Auth\EmailOtpController::class, 'verify'])->name('verification.otp.verify');

// Link-based verification
Route::get('/verify-email/link/{token}', function(string $token) {
    $otp = \App\Models\EmailOtp::where('link_token', $token)->where('used', false)->first();
    if (!$otp || ($otp->expires_at && $otp->expires_at->isPast())) {
        abort(403, 'Invalid or expired verification link.');
    }
    $user = \App\Models\User::where('id', $otp->user_id)->where('email', $otp->email)->first();
    if (!$user) {
        abort(404);
    }
    $userRoleId = \Spatie\Permission\Models\Role::where('name','user')->value('id');
    if ($userRoleId && (int)$user->role_id === (int)$userRoleId) {
        $user->status = 'verified';
        $user->save();
    }
    if (method_exists($user, 'markEmailAsVerified')) {
        $user->markEmailAsVerified();
    }
    $otp->used = true;
    $otp->save();
    return redirect()->route('login')->with('status', 'Email verified. Please login.');
})->name('verification.link');

// Admin - Discount Codes
Route::middleware(['web','auth','ensure_role:admin'])->group(function () {
    Route::get('/admin/discount-codes', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'index'])->name('admin.discounts.index');
    Route::get('/admin/discount-codes/data', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'data'])->name('admin.discounts.data');
    Route::get('/admin/discount-codes/create', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'create'])->name('admin.discounts.create');
    Route::post('/admin/discount-codes', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'store'])->name('admin.discounts.store');
    Route::get('/admin/discount-codes/{discount_code}/edit', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'edit'])->name('admin.discounts.edit');
    Route::put('/admin/discount-codes/{discount_code}', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'update'])->name('admin.discounts.update');
    Route::delete('/admin/discount-codes/{discount_code}', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'destroy'])->name('admin.discounts.destroy');
});
