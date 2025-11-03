{{-- User/Customer Sidebar Component --}}
<ul class="nav flex-column">
    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('home') ? 'active' : '' }}" 
           href="{{ route('home') }}">
            <i class="fas fa-home me-2"></i>
            {{ __('Dashboard') }}
        </a>
    </li>

    <!-- My Orders -->
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('orders.myorder') ? 'active' : '' }}" 
           href="{{ route('orders.myorder') }}">
            <i class="fas fa-shopping-bag me-2"></i>
            {{ __('My Orders') }}
        </a>
    </li>

    <!-- My Addresses -->
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('addresses.*') ? 'active' : '' }}" 
           href="{{ route('addresses.index') }}">
            <i class="fas fa-map-marker-alt me-2"></i>
            {{ __('My Addresses') }}
        </a>
    </li>

    <!-- Profile -->
    <li class="nav-item">
        <a class="nav-link js-ajax-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" 
           href="{{ route('profile.edit') }}">
            <i class="fas fa-user me-2"></i>
            {{ __('Profile Settings') }}
        </a>
    </li>
</ul>
