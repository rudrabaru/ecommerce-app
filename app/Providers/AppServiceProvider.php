<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Modules\Products\Models\Category;

class AppServiceProvider extends ServiceProvider
{
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
