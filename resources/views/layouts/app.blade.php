<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { 
                padding-top: 56px; 
                background-color: #f8f9fa;
            }
            .sidebar { 
                position: fixed; 
                top: 56px; 
                bottom: 0; 
                left: 0; 
                width: 280px; 
                overflow-y: auto; 
                background-color: #ffffff;
                border-right: 1px solid #dee2e6;
                z-index: 1000;
            }
            .content-area { 
                margin-left: 280px; 
                min-height: calc(100vh - 56px);
                padding: 20px;
            }
            @media (max-width: 991.98px) { 
                .sidebar { 
                    transform: translateX(-100%);
                    transition: transform 0.3s ease-in-out;
                    width: 280px;
                }
                .sidebar.show {
                    transform: translateX(0);
                }
                .content-area { 
                    margin-left: 0; 
                }
            }
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 999;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                display: none;
            }
            .sidebar-backdrop.show {
                display: block;
            }
            
            /* Sidebar Navigation Styles */
            .sidebar .nav-link {
                color: #495057;
                padding: 0.75rem 1rem;
                border-radius: 0.375rem;
                margin-bottom: 0.25rem;
                transition: all 0.15s ease-in-out;
                text-decoration: none;
            }
            
            .sidebar .nav-link:hover {
                color: #007bff;
                background-color: rgba(0, 123, 255, 0.1);
            }
            
            .sidebar .nav-link.active {
                color: #fff;
                background-color: #007bff;
                font-weight: 500;
            }
            
            .sidebar .nav-link i {
                width: 1.25rem;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div class="container-fluid">
                <button class="btn btn-outline-light d-lg-none me-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand d-lg-none" href="{{ route('home') }}">{{ config('app.name') }}</a>
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

        <!-- Sidebar -->
        @include('components.sidebar.main')
        
        <!-- Mobile backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        
        <!-- Main content area -->
        <main id="app-content" class="content-area">
            @yield('content')
            {{ $slot ?? '' }}
        </main>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script>
            (function(){
                const content = document.getElementById('app-content');
                if (!content) return;

                // Loading indicator
                function showLoading() {
                    const loader = document.createElement('div');
                    loader.id = 'ajax-loader';
                    loader.innerHTML = `
                        <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `;
                    content.appendChild(loader);
                }

                function hideLoading() {
                    const loader = document.getElementById('ajax-loader');
                    if (loader) loader.remove();
                }

                // Update active sidebar links
                function updateActiveSidebarLinks(url) {
                    const sidebarLinks = document.querySelectorAll('#sidebarMenu .nav-link');
                    sidebarLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === url || 
                            (url.includes(link.getAttribute('href')) && link.getAttribute('href') !== '#')) {
                            link.classList.add('active');
                        }
                    });
                }

                async function ajaxNavigate(url, push = true){
                    try {
                        showLoading();
                        const res = await fetch(url, { 
                            headers: { 
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            } 
                        });
                        
                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}`);
                        }
                        
                        const text = await res.text();
                        const tmp = document.createElement('html');
                        tmp.innerHTML = text;
                        
                        let next = tmp.querySelector('#app-content');
                        if (!next) next = tmp.querySelector('main');
                        
                        if (next) {
                            content.innerHTML = next.innerHTML;
                            updateActiveSidebarLinks(url);
                            
                            // Update page title if available
                            const newTitle = tmp.querySelector('title');
                            if (newTitle) document.title = newTitle.textContent;
                            
                            if (push) history.pushState({ ajax: true }, '', url);
                            
                            // Trigger custom event for other scripts
                            window.dispatchEvent(new CustomEvent('ajaxPageLoaded', { detail: { url } }));
                        } else {
                            window.location.href = url;
                        }
                    } catch(e) { 
                        console.error('AJAX navigation failed:', e);
                        window.location.href = url; 
                    } finally {
                        hideLoading();
                    }
                }

                // Handle sidebar link clicks
                document.addEventListener('click', function(e){
                    const a = e.target.closest('a');
                    if (!a) return;
                    if (!a.classList.contains('js-ajax-link') || a.target === '_blank' || a.hasAttribute('download')) return;
                    const href = a.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                    e.preventDefault();
                    
                    // Close mobile sidebar on navigation
                    if (window.innerWidth < 992) {
                        const sidebar = document.getElementById('sidebarMenu');
                        const backdrop = document.getElementById('sidebarBackdrop');
                        if (sidebar && backdrop) {
                            sidebar.classList.remove('show');
                            backdrop.classList.remove('show');
                        }
                    }
                    
                    ajaxNavigate(href, true);
                });

                // Handle browser back/forward
                window.addEventListener('popstate', function(e){
                    if (e.state && e.state.ajax) {
                        ajaxNavigate(location.href, false);
                    }
                });

                // Initialize active link on page load
                updateActiveSidebarLinks(window.location.href);
            })();

            // Mobile sidebar toggle
            (function(){
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('sidebarMenu');
                const backdrop = document.getElementById('sidebarBackdrop');
                
                function openSidebar() {
                    if (sidebar && backdrop) {
                        sidebar.classList.add('show');
                        backdrop.classList.add('show');
                    }
                }
                
                function closeSidebar() {
                    if (sidebar && backdrop) {
                        sidebar.classList.remove('show');
                        backdrop.classList.remove('show');
                    }
                }
                
                if (sidebarToggle) {
                    sidebarToggle.addEventListener('click', function() {
                        if (sidebar.classList.contains('show')) {
                            closeSidebar();
                        } else {
                            openSidebar();
                        }
                    });
                }
                
                // Close sidebar when clicking backdrop
                if (backdrop) {
                    backdrop.addEventListener('click', closeSidebar);
                }
                
                // Close sidebar on window resize to desktop
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 992) {
                        closeSidebar();
                    }
                });
            })();
        </script>
        
        @stack('scripts')
        
        <script>
            // Global AJAX setup for CSRF
            (function(){
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                if (window.$ && token) {
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } });
                }
            })();
        </script>
    </body>
 </html>
