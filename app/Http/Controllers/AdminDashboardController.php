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
                $role = $user->roles->first();
                $roleName = $role ? $role->name : 'user';
                $roleClass = match(strtolower($roleName)) {
                    'admin' => 'primary',
                    'provider' => 'warning',
                    'user' => 'secondary',
                    'customer' => 'info',
                    default => 'secondary'
                };
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => '<span class="badge bg-' . $roleClass . '">' . ucfirst($roleName) . '</span>',
                    'status' => '<span class="badge bg-success">Active</span>',
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
                $statusClass = $product->is_approved ? 'success' : 'warning';
                $statusText = $product->is_approved ? 'Approved' : 'Pending';
                
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => $product->price,
                    'is_approved' => $product->is_approved,
                    'provider_name' => $product->provider->name ?? 'Unknown',
                    'status' => '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>',
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
