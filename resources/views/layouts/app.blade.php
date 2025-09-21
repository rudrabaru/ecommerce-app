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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

                            // Execute inline scripts inside new content
                            (function executeScripts(container){
                                const scripts = Array.from(container.querySelectorAll('script'));
                                scripts.forEach(oldScript => {
                                    const s = document.createElement('script');
                                    for (const attr of oldScript.attributes) s.setAttribute(attr.name, attr.value);
                                    if (oldScript.src) s.src = oldScript.src; else s.textContent = oldScript.textContent;
                                    oldScript.parentNode.replaceChild(s, oldScript);
                                });
                            })(content);
                            
                            // Execute scripts from @push('scripts') stack
                            const pushScripts = tmp.querySelectorAll('script[data-push="scripts"]');
                            pushScripts.forEach(script => {
                                const s = document.createElement('script');
                                s.textContent = script.textContent;
                                document.head.appendChild(s);
                            });
                            
                            // Update page title if available
                            const newTitle = tmp.querySelector('title');
                            if (newTitle) document.title = newTitle.textContent;
                            
                            if (push) history.pushState({ ajax: true }, '', url);
                            
                            // Trigger custom event for other scripts
                            window.dispatchEvent(new CustomEvent('ajaxPageLoaded', { detail: { url } }));
                            
                            // Re-initialize global components
                            setTimeout(() => {
                                // Re-scan and wire forms
                                document.querySelectorAll('.modal form').forEach(wireForm);
                                
                                // Re-initialize tooltips
                                if (window.bootstrap && bootstrap.Tooltip) {
                                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                                        new bootstrap.Tooltip(el);
                                    });
                                }
                                
                                // Re-initialize popovers
                                if (window.bootstrap && bootstrap.Popover) {
                                    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
                                        new bootstrap.Popover(el);
                                    });
                                }
                            }, 200);
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
            
            // Global DataTable Management System
            (function(){
                // Store DataTable instances
                window.DataTableInstances = window.DataTableInstances || {};
                
                // Function to destroy all existing DataTables
                function destroyAllDataTables() {
                    Object.keys(window.DataTableInstances).forEach(tableId => {
                        if (window.DataTableInstances[tableId] && $.fn.DataTable.isDataTable('#' + tableId)) {
                            try {
                                window.DataTableInstances[tableId].destroy();
                                delete window.DataTableInstances[tableId];
                            } catch (e) {
                                console.warn('Error destroying DataTable:', tableId, e);
                            }
                        }
                    });
                }
                
                // Function to initialize DataTables in current content
                function initializeDataTables() {
                    // Initialize Products DataTable
                    if ($('#products-table').length && !$.fn.DataTable.isDataTable('#products-table')) {
                        const ajaxUrl = window.location.pathname.includes('/admin/') 
                            ? '/admin/products/data' 
                            : '/provider/products/data';
                            
                        window.DataTableInstances['products-table'] = $('#products-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: ajaxUrl,
                            columns: [
                                { data: 'id', name: 'id', width: '60px' },
                                { data: 'title', name: 'title' },
                                { data: 'category', name: 'category.name' },
                                { 
                                    data: 'price', 
                                    name: 'price',
                                    render: function(data) {
                                        return '$' + parseFloat(data).toFixed(2);
                                    },
                                    width: '100px'
                                },
                                { data: 'stock', name: 'stock', width: '80px' },
                                { data: 'status', name: 'is_approved', width: '100px' },
                                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '200px' }
                            ],
                            order: [[0, 'desc']],
                            pageLength: 25,
                            responsive: true,
                            language: {
                                processing: "Loading...",
                                emptyTable: "No data available",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "Showing 0 to 0 of 0 entries",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                lengthMenu: "Show _MENU_ entries",
                                search: "Search:",
                                zeroRecords: "No matching records found"
                            }
                        });
                    }
                    
                    // Initialize Categories DataTable
                    if ($('#categories-table').length && !$.fn.DataTable.isDataTable('#categories-table')) {
                        window.DataTableInstances['categories-table'] = $('#categories-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: '/admin/categories/data',
                            columns: [
                                { data: 'id', name: 'id', width: '60px' },
                                { data: 'name', name: 'name' },
                                { data: 'parent', name: 'parent.name' },
                                { 
                                    data: 'products_count', 
                                    name: 'products_count',
                                    orderable: false,
                                    searchable: false,
                                    width: '100px'
                                },
                                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '150px' }
                            ],
                            order: [[0, 'desc']],
                            pageLength: 25,
                            responsive: true,
                            language: {
                                processing: "Loading...",
                                emptyTable: "No data available",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "Showing 0 to 0 of 0 entries",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                lengthMenu: "Show _MENU_ entries",
                                search: "Search:",
                                zeroRecords: "No matching records found"
                            }
                        });
                    }
                    
                    // Initialize Users DataTable
                    if ($('#users-table').length && !$.fn.DataTable.isDataTable('#users-table')) {
                        window.DataTableInstances['users-table'] = $('#users-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: '/admin/users/data',
                            columns: [
                                { data: 'id', name: 'id', width: '60px' },
                                { data: 'name', name: 'name' },
                                { data: 'email', name: 'email' },
                                { data: 'role', name: 'role', orderable: false, searchable: false },
                                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '150px' }
                            ],
                            order: [[0, 'desc']],
                            pageLength: 25,
                            responsive: true,
                            language: {
                                processing: "Loading...",
                                emptyTable: "No data available",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "Showing 0 to 0 of 0 entries",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                lengthMenu: "Show _MENU_ entries",
                                search: "Search:",
                                zeroRecords: "No matching records found"
                            }
                        });
                    }
                    
                    // Initialize Orders DataTable
                    if ($('#orders-table').length && !$.fn.DataTable.isDataTable('#orders-table')) {
                        const ajaxUrl = window.location.pathname.includes('/admin/') 
                            ? '/admin/orders/data' 
                            : '/provider/orders/data';
                            
                        window.DataTableInstances['orders-table'] = $('#orders-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: ajaxUrl,
                            columns: [
                                { data: 'id', name: 'id', width: '60px' },
                                { data: 'order_number', name: 'order_number' },
                                { data: 'customer_name', name: 'customer_name' },
                                { data: 'product_name', name: 'product_name' },
                                { data: 'total', name: 'total', width: '100px' },
                                { data: 'status', name: 'status', width: '100px' },
                                { data: 'created_at', name: 'created_at', width: '120px' },
                                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '150px' }
                            ],
                            order: [[0, 'desc']],
                            pageLength: 25,
                            responsive: true,
                            language: {
                                processing: "Loading...",
                                emptyTable: "No data available",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "Showing 0 to 0 of 0 entries",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                lengthMenu: "Show _MENU_ entries",
                                search: "Search:",
                                zeroRecords: "No matching records found"
                            }
                        });
                    }
                }
                
                // Listen for AJAX page loaded event
                window.addEventListener('ajaxPageLoaded', function() {
                    // Small delay to ensure DOM is ready
                    setTimeout(function() {
                        destroyAllDataTables();
                        initializeDataTables();
                        
                        // Re-initialize any page-specific functionality
                        if (typeof window.initializePageSpecific === 'function') {
                            window.initializePageSpecific();
                        }
                    }, 100);
                });
                
                // Initialize DataTables on initial page load
                $(document).ready(function() {
                    initializeDataTables();
                });
            })();
        </script>
        
        <script>
            // Global delegated delete handler and generic modal validation
            (function(){
                function reloadAllTables(){
                    document.querySelectorAll('#app-content table').forEach(t => {
                        try { if ($.fn.dataTable.isDataTable(t)) { $(t).DataTable().ajax.reload(null, false); } } catch(e) {}
                    });
                }
                document.addEventListener('click', function(e){
                    const btn = e.target.closest('[data-delete-url], .js-delete');
                    if (!btn) return;
                    const url = btn.getAttribute('data-delete-url') || btn.getAttribute('href');
                    if (!url) return;
                    e.preventDefault();
                    const confirmFn = window.Swal ?
                        () => Swal.fire({ title: 'Are you sure?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' }) :
                        () => Promise.resolve({ isConfirmed: window.confirm('Are you sure?') });
                    confirmFn().then(res => {
                        if (!res.isConfirmed) return;
                        fetch(url, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.success) {
                                reloadAllTables();
                                if (window.Swal) Swal.fire('Deleted', data.message || 'Deleted successfully', 'success'); else alert(data.message || 'Deleted successfully');
                            } else {
                                if (window.Swal) Swal.fire('Error', (data && data.message) || 'Delete failed', 'error'); else alert((data && data.message) || 'Delete failed');
                            }
                        })
                        .catch(err => { console.error('Delete error:', err); if (window.Swal) Swal.fire('Error', 'Delete failed due to an error.', 'error'); else alert('Delete failed due to an error.'); });
                    });
                });

                function wireForm(form){
                    const submit = form.closest('.modal-content')?.querySelector('.modal-footer .btn.btn-primary') || form.querySelector('button[type="submit"].btn-primary') || form.querySelector('button.btn-primary');
                    if (!submit) return;
                    
                    let hasAttemptedSubmit = false;
                    
                    function validate(showErrors = false){
                        let valid = form.checkValidity();
                        form.querySelectorAll('select[required]').forEach(sel => { if (!sel.value) valid = false; });
                        submit.disabled = !valid;
                        
                        // Only show validation errors if we should show them
                        if (showErrors || hasAttemptedSubmit) {
                            form.querySelectorAll('input, textarea, select').forEach(el => {
                                if (el.willValidate) {
                                    if (!el.checkValidity()) {
                                        el.classList.add('is-invalid');
                                        const fb = el.parentElement.querySelector('.invalid-feedback');
                                        if (fb && !fb.textContent) fb.textContent = el.validationMessage;
                                    } else {
                                        el.classList.remove('is-invalid');
                                    }
                                }
                            });
                        }
                    }
                    
                    // Clear validation errors on input/change
                    form.addEventListener('input', function(e) {
                        if (e.target.classList.contains('is-invalid')) {
                            e.target.classList.remove('is-invalid');
                        }
                        validate(false);
                    });
                    
                    form.addEventListener('change', function(e) {
                        if (e.target.classList.contains('is-invalid')) {
                            e.target.classList.remove('is-invalid');
                        }
                        validate(false);
                    });
                    
                    // Show validation errors on blur
                    form.addEventListener('blur', function(e) {
                        if (e.target.willValidate && hasAttemptedSubmit) {
                            validate(true);
                        }
                    }, true);
                    
                    // Mark that user has attempted to submit
                    form.addEventListener('submit', function(e) {
                        hasAttemptedSubmit = true;
                        validate(true);
                        if (!form.checkValidity()) {
                            e.preventDefault();
                        }
                    });
                    
                    // Initial validation without showing errors
                    validate(false);
                }
                function scan(){ document.querySelectorAll('.modal form').forEach(wireForm); }
                document.addEventListener('DOMContentLoaded', scan);
                window.addEventListener('ajaxPageLoaded', function(){ setTimeout(scan, 50); });
                document.addEventListener('shown.bs.modal', scan);
            })();
        </script>
    </body>
 </html>
