<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Products\Models\Product;

class CheckoutController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get cart items based on user authentication status
        if ($user) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart || $cart->items()->count() == 0) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            $cartItems = $cart->items()->with('product')->get()->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->unit_price,
                    'category_id' => optional($item->product)->category_id,
                ];
            });
        } else {
            // For guests, get cart from session
            $sessionCart = session('cart', []);
            if (empty($sessionCart)) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            $ids = array_values(array_column($sessionCart, 'product_id'));
            $categories = $ids === [] ? collect() : Product::whereIn('id', $ids)->pluck('category_id', 'id');
            $cartItems = collect($sessionCart)->map(function ($item) use ($categories) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => (int)($item['quantity'] ?? 0),
                    'price' => (float) ($item['price'] ?? 0),
                    'category_id' => ($categories instanceof \Illuminate\Support\Collection)
                        ? $categories->get($item['product_id'] ?? null)
                        : ($categories[$item['product_id'] ?? null] ?? null),
                ];
            });
        }

        $addresses = $user->addresses()->with(['country', 'state', 'city'])->where('type', 'shipping')->orderBy('created_at', 'asc')->get();
        $paymentMethods = PaymentMethod::getActiveMethods();

        // Calculate cart total
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        // Revalidate discount to ensure consistency before checkout
        $discountAmount = 0.0;
        if ($user) {
            $cartModel = Cart::where('user_id', $user->id)->first();
            if ($cartModel && $cartModel->discount_code) {
                $service = new \App\Services\DiscountService();
                $categoryIds = collect($cartItems)->pluck('category_id')->filter()->unique()->values()->all();
                [$ok, $msg, $recalc] = $service->validateAndCalculate($cartModel->discount_code, $cartItems->all(), $categoryIds, $cartTotal);
                if ($ok) {
                    $discountAmount = $recalc;
                    if ((float)$cartModel->discount_amount !== (float)$recalc) {
                        $cartModel->update(['discount_amount' => $recalc]);
                    }
                } else {
                    $cartModel->update(['discount_code' => null, 'discount_amount' => 0]);
                }
            }
        } else {
            $code = session('cart_discount_code');
            if ($code) {
                $service = new \App\Services\DiscountService();
                $categoryIds = collect($cartItems)->pluck('category_id')->filter()->unique()->values()->all();
                [$ok, $msg, $recalc] = $service->validateAndCalculate($code, $cartItems->all(), $categoryIds, $cartTotal);
                if ($ok) {
                    $discountAmount = $recalc;
                    session()->put('cart_discount', $recalc);
                } else {
                    session()->forget(['cart_discount_code','cart_discount']);
                }
            }
        }

        // Calculate affected items count for discount display
        $affectedItemsCount = 0;
        $discountCode = '';
        if ($user) {
            $cartModel = Cart::where('user_id', $user->id)->first();
            if ($cartModel && $cartModel->discount_code) {
                $discountCode = $cartModel->discount_code;
                $affectedItemsCount = $this->calculateAffectedItemsCount($cartModel->discount_code, $cartItems);
            }
        } else {
            $discountCode = session('cart_discount_code', '');
            if ($discountCode) {
                $affectedItemsCount = $this->calculateAffectedItemsCount($discountCode, $cartItems);
            }
        }

        return view('checkout', ['addresses' => $addresses, 'paymentMethods' => $paymentMethods, 'cartTotal' => $cartTotal, 'discountAmount' => $discountAmount, 'affectedItemsCount' => $affectedItemsCount, 'discountCode' => $discountCode]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Get cart items based on user authentication status
        if ($user) {
            // For logged-in users, get cart from database
            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart || $cart->items()->count() == 0) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            $cartItems = $cart->items()->with('product')->get();
        } else {
            // For guests, get cart from session
            $sessionCart = session('cart', []);
            if (empty($sessionCart)) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            $cartItems = collect($sessionCart);
        }

        $validated = $request->validate([
            'shipping_address_id' => ['required', 'exists:user_addresses,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Verify the address belongs to the user
        $address = UserAddress::where('id', $validated['shipping_address_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

        $userId = Auth::id();
        $created = [];

        DB::beginTransaction();
        try {
            $discountCode = null;
            $discountAmountTotal = 0.0;
            $perItemAllocation = [];

            // Prepare discount allocation if present
            if ($user) {
                $cartModel = Cart::where('user_id', $user->id)->first();
                if ($cartModel && $cartModel->discount_code) {
                    $discountCode = $cartModel->discount_code;
                }
            } else {
                $discountCode = session('cart_discount_code');
            }

            if ($discountCode) {
                $service = new \App\Services\DiscountService();
                // Build normalized cart items for allocation
                $normItems = [];
                if ($user) {
                    foreach ($cartItems as $ci) {
                        $normItems[] = [
                            'product_id' => $ci->product_id,
                            'quantity' => (int)$ci->quantity,
                            'price' => (float)$ci->unit_price,
                            'category_id' => optional($ci->product)->category_id,
                        ];
                    }
                } else {
                    $ids = array_column($cartItems->toArray(), 'product_id');
                    $categories = Product::whereIn('id', $ids)->pluck('category_id', 'id');
                    foreach ($cartItems as $ci) {
                        $normItems[] = [
                            'product_id' => $ci['product_id'],
                            'quantity' => (int)$ci['quantity'],
                            'price' => (float)$ci['price'],
                            'category_id' => $categories[$ci['product_id']] ?? null,
                        ];
                    }
                }

                $perItemAllocation = $service->allocatePerItem(\App\Models\DiscountCode::whereRaw('upper(code) = ?', [strtoupper((string) $discountCode)])->first(), $normItems);
                $discountAmountTotal = array_sum($perItemAllocation);
            }

            // Calculate total order amount
            $totalOrderAmount = 0;
            $orderItems = [];

            foreach ($cartItems as $item) {
                // Handle both database cart items and session cart items
                if ($user) {
                    // Database cart item
                    $product = Product::query()->where('is_approved', true)->findOrFail($item->product_id);
                    $quantity = (int) $item->quantity;
                    $unitPrice = (float) $item->unit_price;
                } else {
                    // Session cart item
                    $product = Product::query()->where('is_approved', true)->findOrFail($item['product_id']);
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['price'];
                }

                $lineTotal = $unitPrice * $quantity;
                $lineDiscount = 0.0;
                if ($discountCode && isset($perItemAllocation[$product->id])) {
                    $lineDiscount = min($perItemAllocation[$product->id], $lineTotal);
                }

                $total = $lineTotal - $lineDiscount;
                $totalOrderAmount += $total;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'line_discount' => $lineDiscount,
                    'total' => $total,
                    'provider_id' => $product->provider_id,
                ];
            }

            // Create single order (always pending initially; gateway confirm will mark paid)
            $order = Order::create([
                'user_id' => $userId,
                'provider_id' => $orderItems[0]['provider_id'] ?? null, // Set primary provider from first item
                'total_amount' => $totalOrderAmount,
                'status' => 'pending',
                'shipping_address' => $address->full_address,
                'shipping_address_id' => $address->id,
                'payment_method_id' => $paymentMethod->id,
                'notes' => $validated['notes'] ?? null,
                'discount_code' => $discountCode,
                'discount_amount' => $discountAmountTotal,
            ]);

            // Create order items
            foreach ($orderItems as $orderItem) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $orderItem['product_id'],
                    'provider_id' => $orderItem['provider_id'],
                    'quantity' => $orderItem['quantity'],
                    'unit_price' => $orderItem['unit_price'],
                    'line_total' => $orderItem['line_total'],
                    'line_discount' => $orderItem['line_discount'],
                    'total' => $orderItem['total'],
                ]);
            }

            // Create payment record (pending initially; confirm will mark paid)
            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $totalOrderAmount,
                'currency' => $paymentMethod->name === 'razorpay' ? 'INR' : 'USD',
                'status' => 'pending',
            ]);

            $created[] = $order->order_number;

            // Increment discount usage once per checkout if discount used
            if ($discountCode && $discountAmountTotal > 0 && $d = \App\Models\DiscountCode::whereRaw('upper(code) = ?', [strtoupper((string) $discountCode)])->first()) {
                (new \App\Services\DiscountService())->incrementUsage($d);
            }

            // Clear cart only for COD at this moment; for online gateways clear after payment success
            if ($paymentMethod->name === 'cod') {
                if ($user) {
                    $cart->items()->delete();
                    if (isset($cart)) {
                        $cart->update(['discount_code' => null, 'discount_amount' => 0]);
                    }
                } else {
                    session()->forget(['cart', 'cart_discount_code', 'cart_discount']);
                }
            }

            DB::commit();

            if ($paymentMethod->name === 'cod') {
                // Send confirmation email for each order (queue for resilience)
                foreach ($created as $orderNumber) {
                    $order = Order::where('order_number', $orderNumber)
                        ->with(['orderItems.product','paymentMethod','user'])
                        ->first();
                    if ($order && $order->user && $order->user->email) {
                        try {
                            dispatch(new \App\Jobs\SendOrderConfirmationEmail($order));
                        } catch (\Throwable $e) {
                            // fallback to sync send
                            try { Mail::to($order->user->email)->send(new OrderConfirmationMail($order)); } catch (\Throwable $_) {}
                        }
                    }
                }

                // If AJAX, return JSON for SweetAlert handling on frontend
                if ($request->wantsJson() || $request->ajax()) {
                    $orderIds = Order::whereIn('order_number', $created)->pluck('id');
                    return response()->json([
                        'success' => true,
                        'payment_method' => 'cod',
                        'order_ids' => $orderIds,
                    ]);
                }

                // Non-AJAX fallback: keep user on checkout with a flash message
                return redirect()->route('checkout')->with('status', 'Order placed successfully');
            }

            // For online payments, return payload for client to initiate gateway
            if ($request->wantsJson() || $request->ajax()) {
                $orderIds = Order::whereIn('order_number', $created)->pluck('id');
                return response()->json([
                    'success' => true,
                    'payment_method' => $paymentMethod->name,
                    'order_ids' => $orderIds,
                ]);
            }

            return redirect()->route('checkout')
                ->with('info', 'Payment required to complete your order.');

        } catch (\Exception $exception) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to place order. Please try again.')
                ->withInput();
        }
    }

    /**
     * Cancel and delete pending orders created prior to an online payment attempt.
     */
    public function cancelPending(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'order_ids' => ['required','array','min:1'],
            'order_ids.*' => ['integer']
        ]);

        DB::transaction(function () use ($validated, $userId) {
            $orders = Order::whereIn('id', $validated['order_ids'])
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->get();

            foreach ($orders as $order) {
                // Delete related rows safely
                \App\Models\OrderItem::where('order_id', $order->id)->delete();
                Payment::where('order_id', $order->id)->delete();
                \App\Models\Transaction::where('order_id', $order->id)->delete();
                $order->delete();
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Calculate the count of unique items affected by a discount code
     */
    private function calculateAffectedItemsCount(string $discountCode, $cartItems)
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
        foreach ($cartItems as $item) {
            $isEligible = empty($allowedCategoryIds) || in_array($item['category_id'], $allowedCategoryIds);
            if ($isEligible) {
                $affectedCount++;
            }
        }

        return $affectedCount;
    }
}