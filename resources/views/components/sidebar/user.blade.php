{{-- User/Customer Sidebar Component --}}
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link js-ajax-link" 
           href="{{ route('home') }}">
            <i class="fas fa-tachometer-alt me-2"></i>
            {{ __('Dashboard') }}
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
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.orders.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.orders.index') ? route('user.orders.index') : '#' }}">
            <i class="fas fa-shopping-bag me-2"></i>
            {{ __('My Orders') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('user.addresses.*') ? 'active' : '' }}" 
           href="{{ Route::has('user.addresses.index') ? route('user.addresses.index') : '#' }}">
            <i class="fas fa-map-marker-alt me-2"></i>
            {{ __('My Addresses') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link js-ajax-link" 
           href="{{ route('profile.edit') }}">
            <i class="fas fa-user me-2"></i>
            {{ __('Profile') }}
        </a>
    </li>
</ul>