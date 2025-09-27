<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\CartItem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class InitializeCart
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure session cart structure
        if (!session()->has('cart')) {
            session(['cart' => []]);
        }

        // Merge session cart into user cart when authenticated
        if (Auth::check()) {
            $user = Auth::user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $sessionCart = session('cart', []);
            if (!empty($sessionCart)) {
                foreach ($sessionCart as $item) {
                    $product = Product::find($item['product_id']);
                    if (!$product) { continue; }
                    $existing = $cart->items()->where('product_id', $product->id)->first();
                    $quantity = max(1, (int) $item['quantity']);
                    if ($existing) {
                        $existing->update(['quantity' => $existing->quantity + $quantity]);
                    } else {
                        $cart->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => (float) $product->price,
                        ]);
                    }
                }
                session()->forget('cart');
            }
        }

        return $next($request);
    }
}


