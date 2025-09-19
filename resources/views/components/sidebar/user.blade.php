{{-- User/Customer Sidebar Component --}}
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}" 
           href="{{ route('user.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>
            {{ __('Dashboard') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.orders.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.orders.index') ? route('user.orders.index') : '#' }}">
            <i class="fas fa-shopping-bag me-2"></i>
            {{ __('My Orders') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.wishlist.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.wishlist.index') ? route('user.wishlist.index') : '#' }}">
            <i class="fas fa-heart me-2"></i>
            {{ __('Wishlist') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.addresses.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.addresses.index') ? route('user.addresses.index') : '#' }}">
            <i class="fas fa-map-marker-alt me-2"></i>
            {{ __('Addresses') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.payment-methods.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.payment-methods.index') ? route('user.payment-methods.index') : '#' }}">
            <i class="fas fa-credit-card me-2"></i>
            {{ __('Payment Methods') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.profile.*') ? 'active' : '' }}" 
           href="{{ route('user.profile') }}">
            <i class="fas fa-user me-2"></i>
            {{ __('Profile') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.support.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.support.index') ? route('user.support.index') : '#' }}">
            <i class="fas fa-life-ring me-2"></i>
            {{ __('Support') }}
        </a>
    </li>
</ul>