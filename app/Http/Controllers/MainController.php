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
            ->get(['id', 'title as name', 'slug', 'price', 'image']);

        return view('index', compact('products'));
    }

    public function cart()
    {
        return view('shopping-cart');
    }

    public function checkout()
    {
        return view('checkout');
    }

    public function shop()
    {
        // Paginated product grid
        $products = Product::query()
            ->where('is_approved', true)
            ->latest('id')
            ->paginate(12, ['id', 'title as name', 'slug', 'price', 'image']);

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
