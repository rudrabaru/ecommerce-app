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
use App\Events\OrderShipped;
use App\Events\OrderDelivered;
use App\Events\OrderCancelled;
use App\Events\OrderItemShipped;
use App\Events\OrderItemDelivered;
use App\Events\OrderItemCancelled;
use App\Listeners\SendOrderShippedNotification;
use App\Listeners\SendOrderDeliveredNotification;
use App\Listeners\SendOrderCancelledNotification;
use App\Listeners\SendOrderItemShippedNotification;
use App\Listeners\SendOrderItemDeliveredNotification;
use App\Listeners\SendOrderItemCancelledNotification;

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
        // Register event listeners
        Event::listen(
            OrderShipped::class,
            SendOrderShippedNotification::class
        );

        Event::listen(
            OrderDelivered::class,
            SendOrderDeliveredNotification::class
        );

        Event::listen(
            OrderCancelled::class,
            SendOrderCancelledNotification::class
        );

        Event::listen(
            OrderItemShipped::class,
            SendOrderItemShippedNotification::class
        );

        Event::listen(
            OrderItemDelivered::class,
            SendOrderItemDeliveredNotification::class
        );

        Event::listen(
            OrderItemCancelled::class,
            SendOrderItemCancelledNotification::class
        );

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
