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
                ];
            });
        } else {
            // For guests, get cart from session
            $sessionCart = session('cart', []);
            if (empty($sessionCart)) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }
            $cartItems = collect($sessionCart)->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => (float) $item['price'],
                ];
            });
        }

        $addresses = $user->addresses()->with(['country', 'state', 'city'])->where('type', 'shipping')->get();
        $paymentMethods = PaymentMethod::getActiveMethods();

        // Calculate cart total
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        return view('checkout', compact('addresses', 'paymentMethods', 'cartTotal'));
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
                
                $total = $unitPrice * $quantity;

                $order = Order::create([
                    'user_id' => $userId,
                    'provider_id' => $product->provider_id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $total,
                    'status' => 'pending',
                    'shipping_address' => $address->full_address,
                    'shipping_address_id' => $address->id,
                    'payment_method_id' => $paymentMethod->id,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Create payment record
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $total,
                    'status' => $paymentMethod->name === 'cod' ? 'pending' : 'pending',
                ]);

                $created[] = $order->order_number;
            }

            // Clear cart based on user type
            if ($user) {
                // Clear database cart
                $cart->items()->delete();
            } else {
                // Clear session cart
                session()->forget(['cart', 'cart_discount_code', 'cart_discount']);
            }
            
            DB::commit();

            // Send confirmation email for each order
            foreach ($created as $orderNumber) {
                $order = Order::where('order_number', $orderNumber)->first();
                if ($order) {
                    Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
                }
            }

            return redirect()->route('orders.success')
                ->with('success', 'Order placed successfully! Order numbers: ' . implode(', ', $created));

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to place order. Please try again.')
                ->withInput();
        }
    }
}