<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Product;

class ProviderDashboardController extends Controller
{
    public function stats()
    {
        $this->authorizeProvider();

        $providerId = Auth::id();

        $stats = [
            'total_products' => Product::where('provider_id', $providerId)->count(),
            // Orders table stores multiple providers in JSON column provider_ids
            'total_orders' => Order::whereJsonContains('provider_ids', $providerId)->count(),
            'pending_orders' => Order::whereJsonContains('provider_ids', $providerId)
                ->whereIn('order_status', ['pending', 'confirmed'])
                ->count(),
            'completed_orders' => Order::whereJsonContains('provider_ids', $providerId)
                ->whereIn('order_status', ['delivered', 'completed'])
                ->count(),
        ];

        return response()->json($stats);
    }

    public function recentOrders()
    {
        $this->authorizeProvider();

        $providerId = Auth::id();

        $orders = Order::with(['user', 'orderItems.product'])
            ->whereJsonContains('provider_ids', $providerId)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($order) use ($providerId) {
                // Only items belonging to this provider
                $providerItems = $order->orderItems->where('provider_id', $providerId);
                $productNames = $providerItems
                    ->map(fn ($it) => optional($it->product)->title)
                    ->filter()
                    ->values()
                    ->implode(', ');
                $subtotal = $providerItems->sum(function ($it) { return (float) $it->total; });
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?? ('ORD-' . $order->id),
                    'customer_name' => optional($order->user)->name,
                    'product_name' => $productNames,
                    'total_amount' => $subtotal,
                    'status' => $order->order_status ?? $order->status ?? 'pending',
                    'created_at' => optional($order->created_at),
                ];
            })->values();

        return response()->json($orders);
    }

    public function myProducts()
    {
        $this->authorizeProvider();

        $providerId = Auth::id();

        $products = Product::where('provider_id', $providerId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $statusClass = $product->is_approved ? 'success' : 'warning';
                $statusText = $product->is_approved ? 'Approved' : 'Pending';

                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'stock' => $product->stock,
                    'is_approved' => $product->is_approved,
                    'status' => '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>',
                    'created_at' => $product->created_at,
                ];
            });

        return response()->json($products);
    }

    private function authorizeProvider(): void
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('provider'), 403);
    }
}
