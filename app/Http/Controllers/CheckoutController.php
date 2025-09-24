<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use Modules\Products\Models\Product;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('shop')->with('status', 'Your cart is empty.');
        }

        $validated = $request->validate([
            'shipping_address' => ['required','string','min:5','max:1000'],
            'notes' => ['nullable','string','max:1000'],
        ]);

        $userId = Auth::id();
        $created = [];

        foreach ($cart as $item) {
            $product = Product::query()->where('is_approved', true)->findOrFail($item['product_id']);

            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price;
            $total = $unitPrice * $quantity;

            $order = Order::create([
                'user_id' => $userId,
                'provider_id' => $product->provider_id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'status' => 'pending',
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $created[] = $order->order_number;
        }

        session()->forget('cart');

        return redirect()->route('shopping.cart')
            ->with('status', 'Order placed: '.implode(', ', $created));
    }
}