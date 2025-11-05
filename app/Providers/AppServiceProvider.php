<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Access\Gate;
use Modules\Products\Models\Category;
use App\Models\Order;
use App\Policies\OrderPolicy;
// Order tracking events/listeners removed

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        \App\Models\OrderItem::class => \App\Policies\OrderItemPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Order tracking event listeners removed

        View::composer('*', function ($view) {
            $headerCategories = collect();
            if (Schema::hasTable('categories')) {
                $query = Category::query();
                if (Schema::hasColumn('categories', 'is_active')) {
                    $query->where('is_active', true);
                }
                $headerCategories = $query
                    ->orderBy('name')
                    ->take(10)
                    ->get(['id', 'name']);
            }

            $view->with('headerCategories', $headerCategories);
        });

        // Protect /admin/* paths globally (excluding /admin/login)
        Route::pushMiddlewareToGroup('web', \App\Http\Middleware\ProtectAdminPaths::class);
    }
}
