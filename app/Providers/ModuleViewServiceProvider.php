<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ModuleViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::addNamespace('admin', base_path('Modules/Admin/Resources/views'));
        View::addNamespace('provider', base_path('Modules/Provider/Resources/views'));
        View::addNamespace('user', base_path('Modules/User/Resources/views'));
    }
}
