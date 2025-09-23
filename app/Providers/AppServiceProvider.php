<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
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
            $headerCategories = Category::query()
                ->when(true, function ($q) {
                    if (Schema::hasColumn('categories', 'is_active')) {
                        $q->where('is_active', true);
                    }
                })
                ->orderBy('name')
                ->take(10)
                ->get(['id', 'name']);

            $view->with('headerCategories', $headerCategories);
        });
    }
}
