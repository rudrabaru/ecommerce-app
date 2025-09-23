<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Products\Models\Product;

class MainController extends Controller
{
    // Landing page (template homepage)
    public function index()
    {
        // Provide products for header dropdown and optional homepage sections
        $products = Product::query()
            ->where('is_approved', true)
            ->latest('id')
            ->take(10)
            ->get(['id', 'title as name', 'price', 'image']);

        return view('index', compact('products'));
    }

    public function cart()
    {
        // For compatibility, redirect to the new cart page that renders session data
        return redirect()->route('cart.index');
    }

    public function checkout()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('shop')->with('status', 'Your cart is empty.');
        }
        $items = collect($cart)->values();
        $subtotal = $items->reduce(fn($c,$i)=> $c + ($i['price'] * $i['quantity']), 0);
        return view('checkout', compact('items','subtotal'));
    }

    public function shop()
    {
        // Paginated product grid
        $query = Product::query()->where('is_approved', true);

        if ($search = request('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = request('category')) {
            $query->where('category_id', $categoryId);
        }

        $sort = request('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest('id');
        }

        $products = $query->paginate(12, ['id', 'title as name', 'price', 'image']);

        return view('shop', compact('products'));
    }

    public function singleProduct($idOrSlug)
    {
        // Resolve by ID or slug to be flexible
        $product = is_numeric($idOrSlug)
            ? Product::query()->where('is_approved', true)->findOrFail($idOrSlug)
            : Product::query()->where('is_approved', true)->where('slug', $idOrSlug)->firstOrFail();

        return view('shop-details', compact('product'));
    }
}
