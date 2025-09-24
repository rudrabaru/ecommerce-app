<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Male_Fashion Template">
    <meta name="keywords" content="Male_Fashion, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'E-Commerce') }}</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap"
    rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/nice-select.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/slicknav.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" type="text/css">
</head>

<body>
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
                        <ul>
                            <li class="{{ request()->routeIs('home') ? 'active' : '' }}"><a href="{{ route('home') }}">Home</a></li>
                            <li class="{{ request()->routeIs('shop*') ? 'active' : '' }}"><a href="{{ route('shop') }}">Shop</a></li>
                            <li class="{{ request()->fullUrlIs(url('/?categories')) ? 'active' : '' }}"><a href="#">Categories</a>
                                <ul class="dropdown">
                                    @isset($headerCategories)
                                        @foreach($headerCategories as $category)
                                            <li><a href="{{ route('shop', ['category' => $category->id]) }}">{{ $category->name }}</a></li>
                                        @endforeach
                                    @endisset
                                </ul>
                            </li>
                            @if (Route::has('login'))
                                @guest
                                    <li><a href="{{ route('login') }}">Log in</a></li>
                                    @if (Route::has('register'))
                                        <li><a href="{{ route('register') }}">Register</a></li>
                                    @endif
                                @endguest
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="canvas__open"><i class="fa fa-bars"></i></div>
        </div>
    </header>
    <!-- Header Section (White) End -->