<?php

namespace Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class StorefrontCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id','name','image','description']);
        return view('categories', compact('categories'));
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        $products = Product::where('is_approved', true)
            ->where('category_id', $category->id)
            ->orderByDesc('id')
            ->paginate(12, ['id','title','price','image']);

        return view('category-products', compact('category', 'products'));
    }
}


