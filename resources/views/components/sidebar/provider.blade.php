{{-- Provider Sidebar Component --}}
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('provider.dashboard') ? 'active' : '' }}" 
           href="{{ route('provider.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>
            {{ __('Dashboard') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('provider.products.*') ? 'active' : '' }}" 
           href="{{ route('provider.products.index') }}">
            <i class="fas fa-box me-2"></i>
            {{ __('My Products') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('provider.orders.*') ? 'active' : '' }}" 
           href="{{ route('provider.orders.index') }}">
            <i class="fas fa-shopping-cart me-2"></i>
            {{ __('Orders') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('provider.payments.*') ? 'active' : '' }}" 
           href="{{ Route::has('provider.payments.index') ? route('provider.payments.index') : '#' }}">
            <i class="fas fa-credit-card me-2"></i>
            {{ __('Payments') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('provider.profile.*') ? 'active' : '' }}" 
           href="{{ route('provider.profile') }}">
            <i class="fas fa-user me-2"></i>
            {{ __('Profile') }}
        </a>
    </li>
</ul>