<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;

class CartController extends Controller
{
    public static function getCartCount()
    {
        if (Auth::check()) {
            // For logged-in users, get count from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                return $cart->items()->sum('quantity');
            }
        } else {
            // For guests, get count from session
            $cart = session('cart', []);
            return collect($cart)->sum('quantity');
        }
        return 0;
    }

    public function index()
    {
        if (Auth::check()) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $items = $cart->items()->with('product')->get()->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->title ?? $item->product->name,
                        'price' => (float) $item->unit_price,
                        'image' => $item->product->image,
                        'quantity' => $item->quantity,
                    ];
                });
                $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
                $discountAmount = $cart->discount_amount ?? 0;
                $total = $subtotal - $discountAmount;
            } else {
                $items = collect();
                $subtotal = 0;
                $discountAmount = 0;
                $total = 0;
            }
        } else {
            // For guests, get cart from session
            $cart = session('cart', []);
            $items = collect($cart)->values();
            $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
            $discountAmount = session('cart_discount', 0);
            $total = $subtotal - $discountAmount;
        }

        return view('shopping-cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required','integer','exists:products,id'],
            'quantity' => ['nullable','integer','min:1']
        ]);

        $product = Product::query()->where('is_approved', true)->findOrFail($validated['product_id']);
        $quantity = max(1, (int)($validated['quantity'] ?? 1));

        if (Auth::check()) {
            // For logged-in users, use database cart
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            
            $cartItem = $cart->items()->where('product_id', $product->id)->first();
            if ($cartItem) {
                $cartItem->quantity += $quantity;
                $cartItem->save();
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);
            }
            
            $cartCount = $cart->items()->sum('quantity');
        } else {
            // For guests, use session cart
            $cart = session()->get('cart', []);
            if (isset($cart[$product->id])) {
                $cart[$product->id]['quantity'] += $quantity;
            } else {
                $cart[$product->id] = [
                    'product_id' => $product->id,
                    'name' => $product->title ?? $product->name,
                    'price' => (float) $product->price,
                    'image' => $product->image,
                    'quantity' => $quantity,
                ];
            }
            session()->put('cart', $cart);
            $cartCount = collect($cart)->sum('quantity');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Added to cart',
                'cart_count' => $cartCount
            ]);
        }

        return Redirect::back()->with('status', 'Added to cart');
    }

    public function update(Request $request, $productId)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1']
        ]);

        if (Auth::check()) {
            // For logged-in users, update database cart
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $cartItem = $cart->items()->where('product_id', $productId)->first();
                if ($cartItem) {
                    $cartItem->quantity = $validated['quantity'];
                    $cartItem->save();
                }
            }
            $cartCount = $cart ? $cart->items()->sum('quantity') : 0;
        } else {
            // For guests, update session cart
            $cart = session('cart', []);
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $validated['quantity'];
                session()->put('cart', $cart);
            }
            $cartCount = collect($cart)->sum('quantity');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount
            ]);
        }

        return redirect()->back();
    }

    public function remove($productId)
    {
        if (Auth::check()) {
            // For logged-in users, remove from database cart
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $cart->items()->where('product_id', $productId)->delete();
            }
            $cartCount = $cart ? $cart->items()->sum('quantity') : 0;
        } else {
            // For guests, remove from session cart
            $cart = session('cart', []);
            if (isset($cart[$productId])) {
                unset($cart[$productId]);
                session()->put('cart', $cart);
            }
            $cartCount = collect($cart)->sum('quantity');
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount
            ]);
        }

        return redirect()->back();
    }

    public function clear()
    {
        if (Auth::check()) {
            // For logged-in users, clear database cart
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $cart->items()->delete();
            }
            $cartCount = 0;
        } else {
            // For guests, clear session cart
            session()->forget('cart');
            $cartCount = 0;
        }   
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount
            ]);
        }

        return redirect()->back();
    }

    public function applyDiscount(Request $request)
    {
        $validated = $request->validate([
            'discount_code' => ['required', 'string', 'max:255']
        ]);

        $discountCode = strtoupper(trim($validated['discount_code']));
        $discountAmount = 0;

        // Validate discount code
        if ($discountCode === 'BARU20') {
            $discountAmount = 20.00;
        } else {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid discount code'
                ], 400);
            }
            return redirect()->back()->with('error', 'Invalid discount code');
        }

        if (Auth::check()) {
            // For logged-in users, save to database cart
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $cart->update([
                'discount_code' => $discountCode,
                'discount_amount' => $discountAmount
            ]);
        } else {
            // For guests, save to session
            session()->put('cart_discount_code', $discountCode);
            session()->put('cart_discount', $discountAmount);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Discount code applied successfully',
                'discount_amount' => $discountAmount
            ]);
        }

        return redirect()->back()->with('success', 'Discount code applied successfully');
    }

    public function removeDiscount(Request $request)
    {
        if (Auth::check()) {
            // For logged-in users, remove from database cart
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $cart->update([
                    'discount_code' => null,
                    'discount_amount' => 0
                ]);
            }
        } else {
            // For guests, remove from session
            session()->forget('cart_discount_code');
            session()->forget('cart_discount');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Discount code removed successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Discount code removed successfully');
    }
}


