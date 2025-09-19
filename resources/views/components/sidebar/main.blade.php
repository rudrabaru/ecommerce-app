{{-- Main Sidebar Component --}}
<nav id="sidebarMenu" class="sidebar">
    <div class="h-100 d-flex flex-column">
        {{-- Logo/Brand Section --}}
        <div class="px-3 py-3 border-bottom text-center">
            <a href="{{ route('home') }}" class="text-decoration-none">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="img-fluid" style="max-height: 40px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="fs-5 fw-bold text-primary" style="display: none;">{{ config('app.name') }}</span>
            </a>
        </div>

        {{-- Role-based Sidebar Navigation --}}
        <div class="px-3 py-2">
            @auth
                @if(Auth::user()->hasRole('admin'))
                    @include('components.sidebar.admin')
                @elseif(Auth::user()->hasRole('provider'))
                    @include('components.sidebar.provider')
                @else
                    @include('components.sidebar.user')
                @endif
            @else
                {{-- Guest Navigation --}}
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-home me-2"></i>
                            {{ __('Home') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            {{ __('Login') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">
                            <i class="fas fa-user-plus me-2"></i>
                            {{ __('Register') }}
                        </a>
                    </li>
                </ul>
            @endauth
        </div>

        {{-- Footer Section --}}
        <div class="px-3 py-2 mt-auto border-top">
            <small class="text-muted d-block text-center">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </small>
        </div>
    </div>
</nav>
