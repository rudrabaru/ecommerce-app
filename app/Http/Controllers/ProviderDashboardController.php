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
            'total_orders' => Order::where('provider_id', $providerId)->count(),
            'pending_orders' => Order::where('provider_id', $providerId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'completed_orders' => Order::where('provider_id', $providerId)
                ->where('status', 'delivered')
                ->count(),
        ];
        
        return response()->json($stats);
    }
    
    public function recentOrders()
    {
        $this->authorizeProvider();
        
        $providerId = Auth::id();
        
        $orders = Order::with(['user', 'product'])
            ->where('provider_id', $providerId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->name,
                    'product_name' => $order->product->title,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });
        
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
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'stock' => $product->stock,
                    'is_approved' => $product->is_approved,
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
