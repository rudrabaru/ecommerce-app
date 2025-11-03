<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Male_Fashion Template">
    <meta name="keywords" content="Male_Fashion, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'E-Commerce') }}</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap"
    rel="stylesheet">

    <!-- Core JavaScript Dependencies -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script>
        // Set up global AJAX settings
        if (typeof jQuery !== 'undefined') {
            jQuery.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        }
    </script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>

    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/nice-select.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/slicknav.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" type="text/css">
    <style>
        .header__right__cart {
            position: relative;
        }
        .cart-icon {
            color: #111111;
            font-size: 18px;
            text-decoration: none;
            position: relative;
        }
        .cart-icon:hover {
            color: #e7ab3c;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e7ab3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Dropdown styles */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 150px;
            z-index: 1000;
            display: none;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-menu li {
            list-style: none;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .dropdown-menu a:hover {
            background: #f8f9fa;
            color: #e7ab3c;
        }
    </style>
</head>

<body data-is-auth="{{ auth()->check() ? '1' : '0' }}">
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>

    @auth
    <!-- Auth Topbar (Black) Begin -->
    <div class="bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-12 d-flex justify-content-end py-2">
                    <form method="POST" action="{{ route('logout') }}" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-light btn-sm">{{ __('Log Out') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Auth Topbar (Black) End -->
    @endauth

    <!-- Header Section (White) Begin -->
    <header class="header">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-3">
                    <div class="header__logo">
                        <a href="{{ route('home') }}"><img src="{{ asset('img/logo.png') }}" alt=""></a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <nav class="header__menu mobile-menu">
                        @php
                            $isAccountNav = request()->routeIs('profile.*') || request()->routeIs('orders.myorder') || request()->routeIs('addresses.*');
                        @endphp
                        <ul>
                            @if($isAccountNav)
                                <li class="{{ request()->routeIs('home') ? 'active' : '' }}"><a href="{{ route('home') }}">Home</a></li>
                                <li class="{{ request()->routeIs('orders.myorder') ? 'active' : '' }}"><a href="{{ route('orders.myorder') }}">My Orders</a></li>
                                <li class="{{ request()->routeIs('addresses.*') ? 'active' : '' }}"><a href="{{ route('addresses.index') }}">My Addresses</a></li>
                            @else
                                <li class="{{ request()->routeIs('home') ? 'active' : '' }}"><a href="{{ route('home') }}">Home</a></li>
                                <li class="{{ request()->routeIs('shop*') ? 'active' : '' }}"><a href="{{ route('shop') }}">Shop</a></li>
                                <li class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"><a href="{{ route('categories.index') }}">Categories</a></li>
                                @if (Route::has('login'))
                                    @guest
                                        <li><a href="{{ route('login') }}">Log in</a></li>
                                        @if (Route::has('register'))
                                            <li><a href="{{ route('register') }}">Register</a></li>
                                        @endif
                                    @else
                                        <li><a href="{{ route('profile.edit') }}">My Profile</a></li>
                                    @endguest
                                @endif
                            @endif
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3 col-md-3">
                    <div class="header__right">
                        {{-- Cart icon will be positioned absolutely at extreme right --}}
                    </div>
                </div>
            </div>
            <div class="canvas__open"><i class="fa fa-bars"></i></div>
        </div>
    </header>
    <!-- Header Section (White) End -->
    
    {{-- Cart Icon: render always; hidden for guests and shown after AJAX login --}}
    <div id="cartIconVisibilityWrapper" style="display: {{ auth()->check() ? 'block' : 'none' }};">
        @include('components.cart-icon')
    </div>

    {{-- Global Login Modal for guests --}}
    @guest
        @include('components.login-modal')
    @endguest