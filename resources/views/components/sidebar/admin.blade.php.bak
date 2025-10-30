{{-- Admin Sidebar Component --}}
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
           href="{{ route('admin.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>
            {{ __('Dashboard') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
           href="{{ route('admin.users.index') }}">
            <i class="fas fa-users me-2"></i>
            {{ __('Users') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.providers.*') ? 'active' : '' }}" 
           href="{{ route('admin.providers.index') }}">
            <i class="fas fa-user-tie me-2"></i>
            {{ __('Providers') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" 
           href="{{ route('admin.products.index') }}">
            <i class="fas fa-box me-2"></i>
            {{ __('Products') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
           href="{{ route('admin.categories.index') }}">
            <i class="fas fa-tags me-2"></i>
            {{ __('Categories') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.discounts.*') ? 'active' : '' }}" 
           href="{{ route('admin.discounts.index') }}">
            <i class="fas fa-ticket-alt me-2"></i>
            {{ __('Discount Codes') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" 
           href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : '#' }}">
            <i class="fas fa-shopping-cart me-2"></i>
            {{ __('Orders') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" 
           href="{{ Route::has('admin.payments.index') ? route('admin.payments.index') : '#' }}">
            <i class="fas fa-credit-card me-2"></i>
            {{ __('Payments') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" 
           href="{{ route('admin.profile') }}">
            <i class="fas fa-cog me-2"></i>
            {{ __('Profile') }}
        </a>
    </li>
</ul>