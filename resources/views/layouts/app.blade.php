<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <style>
            body { padding-top: 56px; }
            .sidebar { position: fixed; top: 56px; bottom: 0; left: 0; width: 250px; overflow-y: auto; }
            .content-wrap { margin-left: 0; }
            @media (min-width: 992px) { .content-wrap { margin-left: 250px; } }
            @media (max-width: 991.98px) { .sidebar { width: 100%; } }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="topNavbar">
                    @auth
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @php
                                    $profileUrl = route('user.profile');
                                    if (Auth::user()->hasRole('admin')) { $profileUrl = route('admin.profile'); }
                                    elseif (Auth::user()->hasRole('provider')) { $profileUrl = route('provider.profile'); }
                                @endphp
                                <li><a class="dropdown-item" href="{{ $profileUrl }}">{{ __('Profile') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">{{ __('Log Out') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    @endauth
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <nav id="sidebarMenu" class="bg-light sidebar collapse show">
                    <div class="position-sticky pt-3">
                        <div class="px-3 py-3 border-bottom text-center">
                            <a href="{{ route('home') }}" class="text-decoration-none">
                                {{ config('app.name') }}
                            </a>
                        </div>
                        <ul class="nav flex-column">
                            @auth
                                @if(Auth::user()->hasRole('admin'))
                                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('Users') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#">{{ __('Reports') }}</a></li>
                                @elseif(Auth::user()->hasRole('provider'))
                                    <li class="nav-item"><a class="nav-link" href="{{ route('provider.dashboard') }}">{{ __('Dashboard') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">{{ __('Products') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('orders.index') }}">{{ __('Orders') }}</a></li>
                                @else
                                    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}">{{ __('Dashboard') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('orders.index') }}">{{ __('Orders') }}</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('user.profile') }}">{{ __('Profile') }}</a></li>
                                @endif
                            @endauth
                        </ul>
                    </div>
                </nav>

                <main class="content-wrap px-4 py-4">
                    @yield('content')
                    {{ $slot ?? '' }}
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    </body>
 </html>
