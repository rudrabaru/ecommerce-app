<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Modules\Products\Models\Product;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $items = collect($cart)->values();
        $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
        return view('shopping-cart', [
            'items' => $items,
            'subtotal' => $subtotal,
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

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Added to cart',
                'cart_count' => collect($cart)->sum('quantity')
            ]);
        }

        return Redirect::back()->with('status', 'Added to cart');
    }

    public function update(Request $request, $productId)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1']
        ]);

        $cart = session('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $validated['quantity'];
            session()->put('cart', $cart);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function remove($productId)
    {
        $cart = session('cart', []);
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            session()->put('cart', $cart);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function clear()
    {
        session()->forget('cart');
        
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }
}


