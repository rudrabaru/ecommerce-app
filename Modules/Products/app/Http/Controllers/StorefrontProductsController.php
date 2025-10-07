<?php

namespace Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use App\Models\DiscountCode;

class StorefrontProductsController extends Controller
{
    /**
     * Display shop page with search, filter, and pagination
     */
    public function shop(Request $request)
    {
        $query = Product::query()->where('is_approved', true)->with(['category', 'provider']);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category')) {
            $query->where('category_id', $categoryId);
        }

        if ($minPrice = $request->get('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->get('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            default:
                $query->latest('id');
        }

        $products = $query->paginate(12)->withQueryString();

        // Build category tree for sidebar
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $headerCategories = Category::orderBy('name')->take(8)->get();
        // Global price bounds for slider UI
        $bounds = Product::selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
        $priceMinBound = (float) ($bounds->min_price ?? 0);
        $priceMaxBound = (float) ($bounds->max_price ?? 0);

        if ($request->ajax()) {
            $html = view('components.product-cards', ['products' => $products])->render();
            $pagination = view('components.pagination', ['paginator' => $products])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $pagination,
                'page' => (int)$products->currentPage(),
            ]);
        }
        return view('shop', compact('products', 'categories', 'headerCategories', 'priceMinBound', 'priceMaxBound'));
    }

    /**
     * Show product details
     */
    public function show($idOrSlug)
    {
        $product = is_numeric($idOrSlug)
            ? Product::where('is_approved', true)->findOrFail($idOrSlug)
            : Product::where('is_approved', true)->where('slug', $idOrSlug)->firstOrFail();

        $product->load(['category', 'provider']);

        $relatedProducts = Product::where('is_approved', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        // Get available discount codes for this product's category
        $discountCodes = $this->getAvailableDiscountCodes($product->category_id);

        return view('shop-details', compact('product', 'relatedProducts', 'discountCodes'));
    }

    /**
     * Get available discount codes for a specific category
     */
    private function getAvailableDiscountCodes($categoryId)
    {
        return DiscountCode::active()
            ->validNow()
            ->notExceededUsage()
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                      ->orWhereHas('categories', function ($q) use ($categoryId) {
                          $q->where('categories.id', $categoryId);
                      });
            })
            ->orderBy('discount_value', 'desc')
            ->get();
    }

    /**
     * Search products (AJAX endpoint)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        if (empty($query)) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('is_approved', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['category'])
            ->take(8)
            ->get(['id', 'title', 'price', 'image', 'slug']);

        return response()->json(['products' => $products]);
    }
}


