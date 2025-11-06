<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductRatingController extends Controller
{
    /**
     * Get eligible products for rating from an order
     */
    public function getEligibleProducts(Request $request, $orderId)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $order = Order::with(['orderItems.product', 'orderItems', 'payment'])
            ->where('user_id', $user->id)
            ->findOrFail($orderId);

        // Check if order is delivered
        if ($order->order_status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be delivered before rating'
            ], 422);
        }

        // Check if payment is paid
        $isPaid = false;
        if ($order->paymentMethod && $order->paymentMethod->name === 'cod') {
            // COD is considered paid if order is delivered
            $isPaid = true;
        } else {
            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'paid')
                ->first();
            $isPaid = (bool)$payment;
        }

        if (!$isPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be completed before rating'
            ], 422);
        }

        // Optional filter by a single product_id (for per-item modal)
        $filterProductId = $request->query('product_id');

        // Get eligible items (delivered, not cancelled)
        $eligibleItems = $order->orderItems()
            ->where('order_status', '!=', OrderItem::STATUS_CANCELLED)
            ->when($filterProductId, function($q) use ($filterProductId) {
                $q->where('product_id', (int)$filterProductId);
            })
            ->with('product')
            ->get();

        // Check existing ratings
        $existingRatings = ProductRating::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->pluck('product_id', 'order_item_id')
            ->toArray();

        $products = [];
        foreach ($eligibleItems as $item) {
            $rating = ProductRating::where('user_id', $user->id)
                ->where('product_id', $item->product_id)
                ->where('order_id', $orderId)
                ->first();

            $products[] = [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_title' => $item->product->title ?? 'Product',
                'product_image' => $item->product->image_url ?? '',
                'quantity' => $item->quantity,
                'rating' => $rating ? [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'review' => $rating->review,
                    'created_at' => $rating->created_at->toAtomString(),
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * Submit rating for a product
     */
    public function submit(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'order_item_id' => ['required', 'exists:order_items,id'],
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ]);

        // Verify order belongs to user
        $order = Order::where('user_id', $user->id)
            ->where('id', $validated['order_id'])
            ->firstOrFail();

        // Verify order is delivered
        if ($order->order_status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be delivered before rating'
            ], 422);
        }

        // Verify order item belongs to order and product
        $orderItem = OrderItem::where('order_id', $validated['order_id'])
            ->where('id', $validated['order_item_id'])
            ->where('product_id', $validated['product_id'])
            ->firstOrFail();

        // Verify not cancelled
        if ($orderItem->order_status === OrderItem::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot rate cancelled items'
            ], 422);
        }

        // Check if rating already exists (one per user, product, order)
        $existing = ProductRating::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->where('order_id', $validated['order_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this product for this order'
            ], 422);
        }

        // Check payment is paid
        $isPaid = false;
        if ($order->paymentMethod && $order->paymentMethod->name === 'cod') {
            $isPaid = true;
        } else {
            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'paid')
                ->first();
            $isPaid = (bool)$payment;
        }

        if (!$isPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be completed before rating'
            ], 422);
        }

        // Create rating (at least one of rating or review must be provided)
        if (empty($validated['rating']) && empty($validated['review'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least a star rating or review text'
            ], 422);
        }

        $rating = ProductRating::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id'],
            'order_id' => $validated['order_id'],
            'order_item_id' => $validated['order_item_id'],
            'rating' => $validated['rating'] ?? null,
            'review' => $validated['review'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'rating' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'review' => $rating->review,
            ],
        ]);
    }

    /**
     * Submit ratings for multiple products in an order (batch submit)
     */
    public function submitBatch(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'exists:order_items,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'items.*.review' => ['nullable', 'string', 'max:5000'],
        ]);

        $order = Order::where('user_id', $user->id)
            ->where('id', $validated['order_id'])
            ->firstOrFail();

        if ($order->order_status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be delivered before rating'
            ], 422);
        }

        // Payment check
        $isPaid = false;
        if ($order->paymentMethod && $order->paymentMethod->name === 'cod') {
            $isPaid = true;
        } else {
            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'paid')
                ->first();
            $isPaid = (bool)$payment;
        }
        if (!$isPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be completed before rating'
            ], 422);
        }

        $created = 0;
        foreach ($validated['items'] as $it) {
            $rating = $it['rating'] ?? null;
            $review = isset($it['review']) ? trim((string)$it['review']) : null;
            // Skip if both empty (optional per-product)
            if (empty($rating) && ($review === null || $review === '')) {
                continue;
            }

            // Validate order item belongs to order and product
            $orderItem = OrderItem::where('order_id', $order->id)
                ->where('id', $it['order_item_id'])
                ->where('product_id', $it['product_id'])
                ->first();
            if (!$orderItem) { continue; }
            if ($orderItem->order_status === OrderItem::STATUS_CANCELLED) { continue; }

            // Enforce single rating constraint
            $existing = ProductRating::where('user_id', $user->id)
                ->where('product_id', $it['product_id'])
                ->where('order_id', $order->id)
                ->first();
            if ($existing) { continue; }

            ProductRating::create([
                'user_id' => $user->id,
                'product_id' => $it['product_id'],
                'order_id' => $order->id,
                'order_item_id' => $it['order_item_id'],
                'rating' => $rating,
                'review' => $review,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => $created > 0 ? 'Ratings submitted successfully' : 'No ratings to submit',
            'created' => $created,
        ]);
    }
    /**
     * Get user's rating for a specific product in an order
     */
    public function getUserRating(Request $request, $orderId, $productId)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $rating = ProductRating::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$rating) {
            // Return 200 with success=false to avoid triggering frontend error handlers
            return response()->json([
                'success' => false,
                'message' => 'No rating found'
            ]);
        }

        return response()->json([
            'success' => true,
            'rating' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'review' => $rating->review,
                'created_at' => $rating->created_at->toAtomString(),
            ],
        ]);
    }

    /**
     * Update an existing rating (user-owned)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ]);

        $rating = ProductRating::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Allow clearing rating or review by sending null/empty
        $rating->rating = array_key_exists('rating', $validated) ? ($validated['rating'] ?? null) : $rating->rating;
        $rating->review = array_key_exists('review', $validated) ? ($validated['review'] ?? null) : $rating->review;
        $rating->save();

        return response()->json(['success' => true, 'message' => 'Rating updated']);
    }

    /**
     * Delete rating (user-owned)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('user'), 403);

        $rating = ProductRating::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $rating->delete();
        return response()->json(['success' => true, 'message' => 'Rating deleted']);
    }
}
