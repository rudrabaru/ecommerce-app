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
        if (is_string($image) && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
            // Rewrite legacy/seeded placeholder domain to a reliable one
            try {
                $parts = parse_url($image);
                if (!empty($parts['host']) && stripos($parts['host'], 'via.placeholder.com') !== false) {
                    $path = $parts['path'] ?? '';
                    $query = $parts['query'] ?? '';
                    $text = '';
                    parse_str($query, $q);
                    if (!empty($q['text'])) { $text = $q['text']; }
                    if (preg_match('#/(\d+x\d+)\.png/([0-9a-fA-F]{3,6})#', $path, $m)) {
                        $size = $m[1];
                        $bg = $m[2];
                        return 'https://placehold.co/' . $size . '/' . $bg . '/ffffff?text=' . urlencode($text ?: '');
                    }
                    if (preg_match('#/(\d+x\d+)#', $path, $m)) {
                        $size = $m[1];
                        return 'https://placehold.co/' . $size . '?text=' . urlencode($text ?: '');
                    }
                    return 'https://placehold.co/600x600?text=' . urlencode($text ?: '');
                }
            } catch (\Throwable $e) {
            }
            return $image;
        }
        return asset('storage/' . ltrim($image, '/'));
    }
    
    /**
     * Get cart count - returns number of UNIQUE products (not total quantity)
     */
    public static function getCartCount()
    {
        if (Auth::check()) {
            // For logged-in users, get count of unique items from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                return $cart->items()->count(); // Count of unique products
            }
        } else {
            // For guests, get count of unique items from session
            $cart = session('cart', []);
            return count($cart); // Count of unique products
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
                    $imageUrl = $item->product ? $item->product->image_url : $this->resolveImageUrl(null);
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->title ?? $item->product->name,
                        'price' => (float) $item->unit_price,
                        'image' => $item->product->image ?? null,
                        'image_url' => $imageUrl,
                        'quantity' => $item->quantity,
                        'category_id' => $item->product->category_id ?? null,
                        'product' => $item->product, // Keep product object for calculateAffectedItemsCount
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
                // Add category_id for discount calculation
                if (isset($i['product_id'])) {
                    $product = \Modules\Products\Models\Product::find($i['product_id']);
                    $i['category_id'] = $product ? $product->category_id : null;
                }
                return $i;
            });
            $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
            $discountAmount = (float) session('cart_discount', 0);
            $total = $subtotal - $discountAmount;
        }

        // Get discount code for display
        $discountCode = '';
        $affectedItemsCount = 0;
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            $discountCode = $cart ? ($cart->discount_code ?? '') : '';
            
            // Calculate affected items count for logged-in users
            if ($discountCode && $cart) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        } else {
            $discountCode = session('cart_discount_code', '');
            
            // Calculate affected items count for guests
            if ($discountCode) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        }

        return view('shopping-cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'discountCode' => $discountCode,
            'affectedItemsCount' => $affectedItemsCount,
        ]);
    }

    public function getCartData()
    {
        if (Auth::check()) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $items = $cart->items()->with('product')->get()->map(function ($item) {
                    $imageUrl = $item->product ? $item->product->image_url : $this->resolveImageUrl(null);
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->title ?? $item->product->name,
                        'price' => (float) $item->unit_price,
                        'image' => $item->product->image ?? null,
                        'image_url' => $imageUrl,
                        'quantity' => $item->quantity,
                        'category_id' => $item->product->category_id ?? null,
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
                // Add category_id for discount calculation
                if (isset($i['product_id'])) {
                    $product = \Modules\Products\Models\Product::find($i['product_id']);
                    $i['category_id'] = $product ? $product->category_id : null;
                }
                return $i;
            });
            $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
            $discountAmount = (float) session('cart_discount', 0);
            $total = $subtotal - $discountAmount;
        }

        // Get discount code for display
        $discountCode = '';
        $affectedItemsCount = 0;
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            $discountCode = $cart ? ($cart->discount_code ?? '') : '';
            
            // Calculate affected items count for logged-in users
            if ($discountCode && $cart) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        } else {
            $discountCode = session('cart_discount_code', '');
            
            // Calculate affected items count for guests
            if ($discountCode) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        }

        return response()->json([
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'discountCode' => $discountCode,
            'affectedItemsCount' => $affectedItemsCount,
        ]);
    }

    public function getEligibleItems(Request $request)
    {
        $discountCode = $request->input('discount_code');
        if (!$discountCode) {
            return response()->json(['eligible_items' => []]);
        }

        // Get discount details
        $discount = \App\Models\DiscountCode::whereRaw('upper(code) = ?', [strtoupper($discountCode)])->first();
        if (!$discount) {
            return response()->json(['eligible_items' => []]);
        }

        // Get allowed category IDs
        $allowedCategoryIds = $discount->categories()->pluck('categories.id')->all();
        if (empty($allowedCategoryIds) && $discount->category_id) {
            $allowedCategoryIds = [$discount->category_id];
        }

        if (Auth::check()) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $items = $cart->items()->with('product')->get();
            } else {
                $items = collect();
            }
        } else {
            // For guests, get cart from session
            $cart = session('cart', []);
            $items = collect($cart)->map(function($item) {
                $product = \Modules\Products\Models\Product::find($item['product_id']);
                return (object) [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'product' => $product
                ];
            });
        }

        // Filter eligible items
        $eligibleItems = [];
        foreach ($items as $item) {
            $product = $item->product ?? null;
            if (!$product) continue;

            $isEligible = empty($allowedCategoryIds) || in_array($product->category_id, $allowedCategoryIds);
            if ($isEligible) {
                $eligibleItems[] = [
                    'name' => $product->title,
                    'price' => (float) ($item->unit_price ?? $item->price),
                    'quantity' => (int) ($item->quantity ?? 1),
                    'total' => (float) ($item->unit_price ?? $item->price) * (int) ($item->quantity ?? 1)
                ];
            }
        }

        return response()->json(['eligible_items' => $eligibleItems]);
    }

    /**
     * Calculate the count of unique items affected by a discount code
     */
    private function calculateAffectedItemsCount(string $discountCode, $items)
    {
        $discount = \App\Models\DiscountCode::whereRaw('upper(code) = ?', [strtoupper($discountCode)])->first();
        if (!$discount) {
            return 0;
        }

        $allowedCategoryIds = $discount->categories()->pluck('categories.id')->all();
        if (empty($allowedCategoryIds) && $discount->category_id) {
            $allowedCategoryIds = [$discount->category_id];
        }

        $affectedCount = 0;
        foreach ($items as $item) {
            // Handle both object and array structures
            if (is_array($item)) {
                $categoryId = $item['category_id'] ?? null;
            } else {
                $product = $item->product ?? null;
                $categoryId = $product ? $product->category_id : null;
            }

            $isEligible = empty($allowedCategoryIds) || in_array($categoryId, $allowedCategoryIds);
            if ($isEligible) {
                $affectedCount++;
            }
        }

        return $affectedCount;
    }

    /**
     * Get updated cart data after quantity update
     */
    private function getUpdatedCartData()
    {
        if (Auth::check()) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $items = $cart->items()->with('product')->get()->map(function ($item) {
                    $imageUrl = $item->product ? $item->product->image_url : $this->resolveImageUrl(null);
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->title ?? $item->product->name,
                        'price' => (float) $item->unit_price,
                        'image' => $item->product->image ?? null,
                        'image_url' => $imageUrl,
                        'quantity' => $item->quantity,
                        'category_id' => $item->product->category_id ?? null,
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
                // Add category_id for discount calculation
                if (isset($i['product_id'])) {
                    $product = \Modules\Products\Models\Product::find($i['product_id']);
                    $i['category_id'] = $product ? $product->category_id : null;
                }
                return $i;
            });
            $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
            $discountAmount = (float) session('cart_discount', 0);
            $total = $subtotal - $discountAmount;
        }

        // Get discount code for display
        $discountCode = '';
        $affectedItemsCount = 0;
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            $discountCode = $cart ? ($cart->discount_code ?? '') : '';
            
            // Calculate affected items count for logged-in users
            if ($discountCode && $cart) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        } else {
            $discountCode = session('cart_discount_code', '');
            
            // Calculate affected items count for guests
            if ($discountCode) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $items);
            }
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'discountCode' => $discountCode,
            'affectedItemsCount' => $affectedItemsCount,
        ];
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
            $this->revalidateAndPersistDiscountForUserCart($cart);
            
            // Get count of unique items (not total quantity)
            $cartCount = $cart->items()->count();
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
            
            // Get count of unique items (not total quantity)
            $cartCount = count($cart);
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
                
                // Revalidate discount if previously applied
                $this->revalidateAndPersistDiscountForUserCart($cart);
            }
            
            // Get count of unique items
            $cartCount = $cart ? $cart->items()->count() : 0;
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
            
            // Get count of unique items
            $cartCount = count($cart);
            $discountAmount = (float) session('cart_discount', 0);
        }

        if ($request->ajax()) {
            // Get updated cart data for complete response
            $cartData = $this->getUpdatedCartData();
            
            return response()->json([
                'success' => true,
                'cart_count' => $cartCount,
                'discount_amount' => $discountAmount,
                'cart_data' => $cartData
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
                
                // Revalidate discount if previously applied
                $this->revalidateAndPersistDiscountForUserCart($cart);
            }
            
            // Get count of unique items
            $cartCount = $cart ? $cart->items()->count() : 0;
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
            
            // Get count of unique items
            $cartCount = count($cart);
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

}