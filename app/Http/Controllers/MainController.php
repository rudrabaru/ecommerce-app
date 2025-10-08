<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Products\Models\Product;

class MainController extends Controller
{
    // Landing page (template homepage)
    public function index()
    {
        // New Arrivals: products created within the last 24 hours
        $products = Product::query()
            ->where('is_approved', true)
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'title as name', 'price', 'image']);

        return view('index', compact('products'));
    }

    public function cart()
    {
        // For compatibility, redirect to the new cart page that renders session data
        return redirect()->route('cart.index');
    }


    // Storefront routes are served by Modules\Products\Http\Controllers\StorefrontProductsController
}
