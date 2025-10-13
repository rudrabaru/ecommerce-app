<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\DiscountService;
use Modules\Products\Models\Product as ProductModel;

class CartController extends Controller
{
    private function resolveImageUrl($image)
    {
        if (!$image) {
            return asset('img/product/product-1.jpg');
        }
        // If it already looks like a full URL (e.g., seeded placeholder), return as-is
        if (is_string($image) && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
            return $image;
        }
        return asset('storage/' . $image);
    }
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
                    $image = $item->product->image;
                    $imageUrl = $this->resolveImageUrl($image);
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->title ?? $item->product->name,
                        'price' => (float) $item->unit_price,
                        'image' => $item->product->image,
                        'image_url' => $imageUrl,
                        'quantity' => $item->quantity,
                    ];
                });
                $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
                $discountAmount = (float) ($cart->discount_amount ?? 0);
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
            $items = collect($cart)->values()->map(function($i){
                $image = $i['image'] ?? null;
                $imageUrl = $this->resolveImageUrl($image);
                $i['image_url'] = $imageUrl;
                return $i;
            });
            $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
            $discountAmount = (float) session('cart_discount', 0);
            $total = $subtotal - $discountAmount;
        }

        // Get discount code for display
        $discountCode = '';
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            $discountCode = $cart ? ($cart->discount_code ?? '') : '';
        } else {
            $discountCode = session('cart_discount_code', '');
        }

        return view('shopping-cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'discountCode' => $discountCode,
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
            
            // Revalidate discount if previously applied
            $cartCount = $cart->items()->sum('quantity');
            $this->revalidateAndPersistDiscountForUserCart($cart);
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
            // Revalidate discount if previously applied
            $this->revalidateAndPersistDiscountForSessionCart();
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
            // Revalidate discount if previously applied
            if ($cart) { $this->revalidateAndPersistDiscountForUserCart($cart); }
            $cartCount = $cart ? $cart->items()->sum('quantity') : 0;
            $discountAmount = $cart ? (float)($cart->fresh()->discount_amount ?? 0) : 0;
        } else {
            // For guests, update session cart
            $cart = session('cart', []);
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $validated['quantity'];
                session()->put('cart', $cart);
            }
            // Revalidate discount if previously applied
            $this->revalidateAndPersistDiscountForSessionCart();
            $cartCount = collect($cart)->sum('quantity');
            $discountAmount = (float) session('cart_discount', 0);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount,
                'discount_amount' => $discountAmount
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
            // Revalidate discount if previously applied
            if ($cart) { $this->revalidateAndPersistDiscountForUserCart($cart); }
            $cartCount = $cart ? $cart->items()->sum('quantity') : 0;
            $discountAmount = $cart ? (float)($cart->fresh()->discount_amount ?? 0) : 0;
        } else {
            // For guests, remove from session cart
            $cart = session('cart', []);
            if (isset($cart[$productId])) {
                unset($cart[$productId]);
                session()->put('cart', $cart);
            }
            // Revalidate discount if previously applied
            $this->revalidateAndPersistDiscountForSessionCart();
            $cartCount = collect($cart)->sum('quantity');
            $discountAmount = (float) session('cart_discount', 0);
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount,
                'discount_amount' => $discountAmount
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
                // Also clear discount information
                $cart->update([
                    'discount_code' => null,
                    'discount_amount' => 0
                ]);
            }
            $cartCount = 0;
        } else {
            // For guests, clear session cart and discount
            session()->forget(['cart', 'cart_discount_code', 'cart_discount']);
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
        $service = new DiscountService();

        // Build cart items and category IDs
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();
            $items = $cart ? $cart->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'price' => (float)$i->unit_price,
                'category_id' => optional($i->product)->category_id,
            ]) : collect();
        } else {
            $sessionCart = array_values(session('cart', []));
            try {
                $ids = array_column($sessionCart, 'product_id');
                $categories = empty($ids) ? collect() : ProductModel::whereIn('id', $ids)->pluck('category_id', 'id');
                $items = collect($sessionCart)->map(function ($i) use ($categories) {
                    $pid = isset($i['product_id']) ? (int)$i['product_id'] : null;
                    $catId = null;
                    if (!is_null($pid)) {
                        if ($categories instanceof \Illuminate\Support\Collection) {
                            $catId = $categories->get($pid);
                        } else if (is_array($categories)) {
                            $catId = isset($categories[$pid]) ? $categories[$pid] : null;
                        }
                    }
                    return [
                        'product_id' => $pid,
                        'quantity' => (int)($i['quantity'] ?? 0),
                        'price' => (float)($i['price'] ?? 0.0),
                        'category_id' => $catId,
                    ];
                });
            } catch (\Throwable $e) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Invalid cart data for discount application'], 400);
                }
                return redirect()->back()->with('error', 'Invalid cart data for discount application');
            }
        }

        $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0.0);
        $categoryIds = $items->pluck('category_id')->filter()->unique()->values()->all();

        [$ok, $message, $discountAmount, $discount, $affectedItems] = $service->validateAndCalculate($discountCode, $items->all(), $categoryIds, $subtotal);
        if (!$ok) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return redirect()->back()->with('error', $message);
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
                'message' => $message,
                'discount_amount' => $discountAmount,
                'discount_code' => $discountCode,
                'affected_items' => (int) $affectedItems
            ]);
        }

        return redirect()->back()->with('success', 'Discount code applied successfully');
    }

    private function revalidateAndPersistDiscountForUserCart(?Cart $cart): void
    {
        if (!$cart) return;
        if (!$cart->discount_code) return;
        $cart->load('items.product');
        $items = $cart->items->map(fn($i) => [
            'product_id' => $i->product_id,
            'quantity' => $i->quantity,
            'price' => (float)$i->unit_price,
            'category_id' => optional($i->product)->category_id,
        ]);
        $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0.0);
        $categoryIds = $items->pluck('category_id')->filter()->unique()->values()->all();
        $service = new DiscountService();
        [$ok, $message, $discountAmount] = $service->validateAndCalculate($cart->discount_code, $items->all(), $categoryIds, $subtotal);
        if ($ok) {
            $cart->update(['discount_amount' => $discountAmount]);
        } else {
            $cart->update(['discount_code' => null, 'discount_amount' => 0]);
        }
    }

    private function revalidateAndPersistDiscountForSessionCart(): void
    {
        $code = session('cart_discount_code');
        if (!$code) return;
        $sessionCart = array_values(session('cart', []));
        if (empty($sessionCart)) {
            session()->forget(['cart_discount_code','cart_discount']);
            return;
        }
        $ids = array_column($sessionCart, 'product_id');
        $categories = empty($ids) ? collect() : ProductModel::whereIn('id', $ids)->pluck('category_id', 'id');
        $items = collect($sessionCart)->map(function ($i) use ($categories) {
            $pid = isset($i['product_id']) ? (int)$i['product_id'] : null;
            $catId = null;
            if (!is_null($pid)) {
                if ($categories instanceof \Illuminate\Support\Collection) {
                    $catId = $categories->get($pid);
                } else if (is_array($categories)) {
                    $catId = isset($categories[$pid]) ? $categories[$pid] : null;
                }
            }
            return [
                'product_id' => $pid,
                'quantity' => (int)($i['quantity'] ?? 0),
                'price' => (float)($i['price'] ?? 0.0),
                'category_id' => $catId,
            ];
        });
        $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0.0);
        $categoryIds = $items->pluck('category_id')->filter()->unique()->values()->all();
        $service = new DiscountService();
        [$ok, $message, $discountAmount] = $service->validateAndCalculate($code, $items->all(), $categoryIds, $subtotal);
        if ($ok) {
            session()->put('cart_discount', $discountAmount);
        } else {
            session()->forget(['cart_discount_code','cart_discount']);
        }
    }

    public function removeDiscount(Request $request)
    {
        try {
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
                    'message' => 'Discount code removed successfully',
                    'discount_amount' => 0
                ]);
            }

            return redirect()->back()->with('success', 'Discount code removed successfully');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error removing discount code'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error removing discount code');
        }
    }

    public function dropdown()
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $items = $cart->items()->with('product')->get()->take(3);
                $total = $cart->items()->sum('quantity');
            } else {
                $items = collect();
                $total = 0;
            }
        } else {
            $cart = session('cart', []);
            $items = collect($cart)->values()->take(3);
            $total = collect($cart)->sum('quantity');
        }

        $subtotal = $items->reduce(function($carry, $item) {
            return $carry + (($item['price'] ?? $item->unit_price) * ($item['quantity'] ?? $item->quantity));
        }, 0);

        $html = '';
        foreach ($items as $item) {
            $name = $item['name'] ?? $item->product->title;
            $price = $item['price'] ?? $item->unit_price;
            $quantity = $item['quantity'] ?? $item->quantity;
            $image = $item['image_url'] ?? ($item['image'] ?? $item->product->image);
            
            $html .= '<div class="cart-dropdown-item" style="padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                <img src="' . $image . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                <div class="flex-fill">
                    <div style="font-size: 14px; font-weight: 500;">' . $name . '</div>
                    <div style="font-size: 12px; color: #666;">Qty: ' . $quantity . ' × $' . number_format($price, 2) . '</div>
                </div>
                <div style="font-weight: 600;">$' . number_format($price * $quantity, 2) . '</div>
            </div>';
        }

        if ($items->isEmpty()) {
            $html = '<div style="padding: 20px; text-align: center; color: #666;">Your cart is empty</div>';
        }

        return response()->json([
            'items' => $html,
            'total' => $subtotal,
            'itemCount' => $total
        ]);
    }
}


