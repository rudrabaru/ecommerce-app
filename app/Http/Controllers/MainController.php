<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MainController extends Controller
{
    //
    public function index()
    {
        return view('index');
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
        return view('shop');
    }
    public function singleProduct($id)
    {
        // Optional: fetch product from DB
        // $product = Product::findOrFail($id);
        return view('shop-details', compact('id'));
    }
}