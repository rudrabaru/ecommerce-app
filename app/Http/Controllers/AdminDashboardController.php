<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $this->authorizeAdmin();
        
        $stats = [
            'total_users' => User::count(),
            'total_providers' => User::role('provider')->count(),
            'total_categories' => Category::count(),
            'total_products' => Product::count(),
        ];
        
        return response()->json($stats);
    }
    
    public function recentUsers()
    {
        $this->authorizeAdmin();
        
        $users = User::with('roles')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'user',
                    'created_at' => $user->created_at,
                ];
            });
        
        return response()->json($users);
    }
    
    public function recentProducts()
    {
        $this->authorizeAdmin();
        
        $products = Product::with(['provider', 'category'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $product->price,
                    'is_approved' => $product->is_approved,
                    'provider_name' => $product->provider->name ?? 'Unknown',
                    'created_at' => $product->created_at,
                ];
            });
        
        return response()->json($products);
    }
    
    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);
    }
}
