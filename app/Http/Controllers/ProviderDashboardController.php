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
            // Use existence of provider's items to count orders robustly
            'total_orders' => Order::whereExists(function($q) use ($providerId){
                    $q->selectRaw('1')->from('order_items')
                      ->whereColumn('order_items.order_id', 'orders.id')
                      ->where('order_items.provider_id', $providerId);
                })->count(),
            'pending_orders' => Order::whereExists(function($q) use ($providerId){
                    $q->selectRaw('1')->from('order_items')
                      ->whereColumn('order_items.order_id', 'orders.id')
                      ->where('order_items.provider_id', $providerId);
                })
                ->whereIn('order_status', ['pending', 'confirmed'])
                ->count(),
            'completed_orders' => Order::whereExists(function($q) use ($providerId){
                    $q->selectRaw('1')->from('order_items')
                      ->whereColumn('order_items.order_id', 'orders.id')
                      ->where('order_items.provider_id', $providerId);
                })
                ->whereIn('order_status', ['delivered', 'completed'])
                ->count(),
        ];

        return response()->json($stats);
    }

    public function recentOrders()
    {
        $this->authorizeProvider();

        $providerId = Auth::id();

        $orders = Order::with(['user', 'orderItems.product', 'orderItems.provider'])
            ->whereExists(function($q) use ($providerId){
                $q->selectRaw('1')->from('order_items')
                  ->whereColumn('order_items.order_id', 'orders.id')
                  ->where('order_items.provider_id', $providerId);
            })
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
                // Use original line totals minus provider discount share for a fair recent total
                $subtotal = $providerItems->sum(function ($it) { return (float) ($it->line_total ?? $it->total); });
                $providerDiscount = $providerItems->sum(function ($it) { return (float) ($it->line_discount ?? 0); });
                $final = max(0, (float) $subtotal - (float) $providerDiscount);
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?? ('ORD-' . $order->id),
                    'customer_name' => optional($order->user)->name,
                    'product_name' => $productNames,
                    'total_amount' => $final,
                    'status' => $order->order_status ?? $order->status ?? 'pending',
                    'created_at' => optional($order->created_at)
                        ? $order->created_at->copy()->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s')
                        : null,
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
