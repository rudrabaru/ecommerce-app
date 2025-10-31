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
        $query = Product::query()
            ->where(function($q){ $q->where('is_approved', true)->orWhereNull('is_approved'); })
            ->with(['category', 'provider']);

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

        $sort = $request->get('sort', 'featured');
        switch ($sort) {
            case 'featured':
                // Featured = show everything, default ordering by most recent
                $query->orderByDesc('products.id');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'best_reviews':
                // Prepare for future reviews table: products left-joined with reviews, sorted by avg rating desc
                $query->leftJoin('reviews as r', 'r.product_id', '=', 'products.id')
                      ->select('products.*')
                      ->selectRaw('COALESCE(AVG(r.rating), 0) as avg_rating')
                      ->groupBy('products.id')
                      ->orderByDesc('avg_rating')
                      ->orderByDesc('products.id');
                break;
            case 'trending':
                // Prepare for future orders table: sum of quantities sold
                $query->leftJoin('order_items as oi', 'oi.product_id', '=', 'products.id')
                      ->select('products.*')
                      ->selectRaw('COALESCE(SUM(oi.quantity), 0) as total_sold')
                      ->groupBy('products.id')
                      ->orderByDesc('total_sold')
                      ->orderByDesc('products.id');
                break;
            case 'latest':
                // Latest = only items created within last 24 hours
                $query->where('products.created_at', '>=', now()->subDay())
                      ->orderByDesc('products.created_at')
                      ->orderByDesc('products.id');
                break;
            default:
                // Default fallback behaves like featured
                $query->orderByDesc('products.id');
        }

        // Ensure select columns are correct when joins are used
        if (empty($query->getQuery()->columns)) {
            $query->select('products.*');
        }
        $products = $query->paginate(12)->withQueryString();

        // Build category tree for sidebar
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $headerCategories = Category::orderBy('name')->take(8)->get();
        // Global price bounds for slider UI
        $bounds = Product::selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
        $priceMinBound = (float) ($bounds->min_price ?? 0);
        $priceMaxBound = (float) ($bounds->max_price ?? 0);

        $showNewBadge = $sort === 'latest';
        if ($request->ajax()) {
            $html = view('components.product-cards', ['products' => $products, 'showNewBadge' => $showNewBadge])->render();
            $pagination = view('components.pagination', ['paginator' => $products])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $pagination,
                'page' => (int)$products->currentPage(),
            ]);
        }
        return view('shop', compact('products', 'categories', 'headerCategories', 'priceMinBound', 'priceMaxBound', 'showNewBadge'));
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

        $relatedProducts = Product::where(function($q){ $q->where('is_approved', true)->orWhereNull('is_approved'); })
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
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                })->orWhereDoesntHave('categories'); // treat no categories as global
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
