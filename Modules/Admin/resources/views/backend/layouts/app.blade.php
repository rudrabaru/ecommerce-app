<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" href="{{ config('settings.site_favicon') ?? asset('favicon.ico') }}" type="image/x-icon">

    @include('backend.layouts.partials.theme-colors')
    @yield('before_vite_build')

    @livewireStyles
    @viteReactRefresh
    @vite(['resources/js/app.js', 'resources/css/app.css'], 'build')
    @stack('styles')
    @yield('before_head')

    @if (!empty(config('settings.global_custom_css')))
    
    @endif

    @include('backend.layouts.partials.integration-scripts')

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_HEAD, '') !!}
</head>

<body x-data="{
    page: 'ecommerce',
    darkMode: false,
    stickyMenu: false,
    sidebarToggle: $persist(false),
    scrollTop: false
}"
x-init="
    darkMode = JSON.parse(localStorage.getItem('darkMode')) ?? false;
    $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)));
    $watch('sidebarToggle', value => localStorage.setItem('sidebarToggle', JSON.stringify(value)));
    
    // Add loaded class for smooth fade-in
    $nextTick(() => {
        document.querySelector('.app-container').classList.add('loaded');
    });
"
:class="{ 'dark bg-gray-900': darkMode === true }">

    <!-- Page Wrapper with smooth fade-in -->
    <div class="app-container flex h-screen overflow-hidden">
        @include('backend.layouts.partials.sidebar.logo')

        <!-- Content Area -->
        <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto bg-body dark:bg-gray-900">
            <!-- Small Device Overlay -->
            <div @click="sidebarToggle = false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                class="fixed w-full h-screen z-9 bg-gray-900/50"></div>
            <!-- End Small Device Overlay -->

            @include('backend.layouts.partials.header.index')

            <!-- Main Content -->
            <main>
                @hasSection('admin-content')
                    @yield('admin-content')
                @else
                    @isset($slot) {{ $slot }} @endisset
                @endif
            </main>
            <!-- End Main Content -->
        </div>
    </div>

    <x-toast-notifications />

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_FOOTER_BEFORE, '') !!}

    @stack('scripts')
    
    <!-- Admin Navigation and State Management -->
    @vite('Modules/Admin/resources/js/admin-navigation.js')

    @if (!empty(config('settings.global_custom_js')))
    
    @endif

    @livewireScriptConfig

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_FOOTER_AFTER, '') !!}

    <!-- Global Admin Functions - Must be loaded first -->
    <script>
    // Global Admin Panel Functions - Defined immediately for global access
    window.AdminPanel = window.AdminPanel || {};
    
    // Utility functions
    window.AdminPanel.qs = function(sel, root) { 
        return (root || document).querySelector(sel); 
    };
    
    window.AdminPanel.qsa = function(sel, root) { 
        return Array.prototype.slice.call((root || document).querySelectorAll(sel)); 
    };
    
    // Navigation and state management functions
    window.AdminPanel.navigateTo = function(url, page) {
        if (window.AdminState && window.AdminState.loadPage) {
            window.AdminState.loadPage(url, page);
        } else {
            window.location.href = url;
        }
    };

    // Global form submission function
    window.submitForm = function(formId){
        console.log('submitForm called with formId:', formId);
        var form = document.getElementById(formId);
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    };

    // Global discount modal opener
    window.openDiscountModal = function(id){
        console.log('openDiscountModal called with id:', id);
        var target = '#discountModal';
        var modal = window.AdminPanel.qs(target);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'discountModal';
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Discount Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="p-3 text-center text-muted">Loading...</div></div></div></div>';
            document.body.appendChild(modal);
        }
        
        var url = id ? '/admin/discount-codes/' + id + '/edit' : '/admin/discount-codes/create';
        fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
            .then(r=>r.text()).then(function(html){
                var body = modal.querySelector('.modal-body');
                body.innerHTML = html;
                // Rebind forms in the modal
                if (window.AdminPanel.rebindAll) {
                    window.AdminPanel.rebindAll(body);
                }
                try {
                    var bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                } catch (e) {
                    console.error('Bootstrap modal error:', e);
                    if (window.jQuery) jQuery(modal).modal('show');
                }
            }).catch(function(err){
                console.error('Discount modal error:', err);
                if (window.Swal) Swal.fire('Error','Unable to open discount modal','error');
            });
    };

    // Debug function availability
    window.debugAdminFunctions = function() {
        console.log('=== Admin Functions Debug ===');
        console.log('openCreateUserModal:', typeof window.openCreateUserModal);
        console.log('openCreateProviderModal:', typeof window.openCreateProviderModal);
        console.log('openUserModal:', typeof window.openUserModal);
        console.log('openDiscountModal:', typeof window.openDiscountModal);
        console.log('submitForm:', typeof window.submitForm);
        console.log('AdminPanel.rebindAll:', typeof window.AdminPanel?.rebindAll);
        console.log('jQuery available:', typeof window.jQuery);
        console.log('Bootstrap available:', typeof window.bootstrap);
        console.log('============================');
    };
    
    console.log('Global admin functions initialized');
    </script>

    (function(){
        // Centralized AJAX navigation + DataTables + modal rebinds for Admin UI
        var BUSY = false;

        function qs(sel, root){ return window.AdminPanel.qs(sel, root); }
        function qsa(sel, root){ return window.AdminPanel.qsa(sel, root); }

        function reinitDataTables(ctx){
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable) return;
            var $ = window.jQuery;
            window.DataTableInstances = window.DataTableInstances || {};
            
            qsa('table[data-dt-url]', ctx).forEach(function(table){
                var $table = $(table);
                var tableId = table.id || 'table-' + Math.random().toString(36).substr(2, 9);
                if (!table.id) table.id = tableId;
                
                // Destroy existing instance if it exists
                if ($.fn.dataTable.isDataTable($table)) {
                    console.log('Destroying existing DataTable:', tableId);
                    $table.DataTable().destroy();
                }
                
                // Create new instance with dynamic columns based on table headers
                var columns = [];
                var headers = table.querySelectorAll('thead th');
                headers.forEach(function(header, index){
                    var dataAttr = header.getAttribute('data-column') || header.textContent.toLowerCase().replace(/\s+/g, '_');
                    var orderable = header.getAttribute('data-orderable') !== 'false';
                    var searchable = header.getAttribute('data-searchable') !== 'false';
                    var width = header.getAttribute('data-width') || 'auto';
                    
                    columns.push({
                        data: dataAttr,
                        name: dataAttr,
                        width: width,
                        orderable: orderable,
                        searchable: searchable
                    });
                });
                
                console.log('Initializing DataTable:', tableId, 'with columns:', columns);
                
                window.DataTableInstances[tableId] = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: $table.data('dt-url'),
                    pageLength: $table.data('dt-page-length') || 25,
                    order: JSON.parse($table.attr('data-dt-order') || '[[0, "desc"]]'),
                    columns: columns,
                    drawCallback: function() {
                        // Rebind events after each draw
                        bindEditButtons(ctx);
                        bindToggleActions(ctx);
                        bindDeleteButtons(ctx);
                    }
                });
            });
        }

        function bindAjaxForms(ctx){
            qsa('form[data-ajax-submit="1"]', ctx).forEach(function(form){
                if (form.getAttribute('data-bound')) return;
                form.setAttribute('data-bound','1');
                
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var formData = new FormData(form);
                    var url = form.action || window.location.href;
                    var method = formData.get('_method') || 'POST';
                    
                    // Show loading state
                    var submitBtn = form.querySelector('button[type="submit"], button[onclick*="submitForm"]');
                    var spinner = form.querySelector('.spinner-border');
                    if (submitBtn) submitBtn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    
                    console.log('Submitting form:', url, method);
                    
                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': qs('meta[name="csrf-token"]').content
                        }
                    })
                    .then(r => r.json())
                    .then(function(data){
                        console.log('Form submission response:', data);
                        if (data.success) {
                            if (window.Swal) Swal.fire('Success', data.message || 'Saved successfully!', 'success');
                            var modal = form.closest('.modal');
                            if (modal) {
                                var bsModal = bootstrap.Modal.getInstance(modal);
                                if (bsModal) bsModal.hide();
                            }
                            // Reload DataTable if specified
                            var reloadTable = form.getAttribute('data-reload-table');
                            if (reloadTable && window.DataTableInstances && window.DataTableInstances[reloadTable]) {
                                window.DataTableInstances[reloadTable].ajax.reload();
                            }
                        } else {
                            // Clear previous errors
                            qsa('.is-invalid', form).forEach(function(el){ el.classList.remove('is-invalid'); });
                            qsa('.invalid-feedback', form).forEach(function(el){ el.textContent = ''; });
                            
                            // Show validation errors
                            Object.keys(data.errors || {}).forEach(function(field){
                                var input = form.querySelector('[name="' + field + '"]');
                                if (input) {
                                    input.classList.add('is-invalid');
                                    var feedback = input.nextElementSibling;
                                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                                        feedback.textContent = data.errors[field][0];
                                    }
                                }
                            });
                            if (window.Swal) Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
                        }
                    })
                    .catch(function(err){
                        console.error('Form submission error:', err);
                        if (window.Swal) Swal.fire('Error', 'An error occurred while saving.', 'error');
                    })
                    .finally(function(){
                        if (submitBtn) submitBtn.disabled = false;
                        if (spinner) spinner.classList.add('d-none');
                    });
                });
            });
        }

        function bindEditButtons(ctx){
            qsa('[data-edit-url], .btn-edit, .edit-user, .edit-provider', ctx).forEach(function(btn){
                if (btn.getAttribute('data-bound')) return;
                btn.setAttribute('data-bound','1');
                
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    var url = btn.getAttribute('data-edit-url') || btn.getAttribute('href');
                    var userId = btn.getAttribute('data-id') || btn.getAttribute('data-user-id');
                    
                    if (userId) {
                        url = '/admin/users/' + userId + '/edit';
                    }
                    
                    if (!url) return;
                    
                    console.log('Edit button clicked, fetching:', url);
                    
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(r=>r.json()).then(function(data){
                            console.log('Edit data received:', data);
                            // Determine which modal to open based on context
                            var isProvider = window.location.pathname.includes('providers') || (data.user && data.user.roles && data.user.roles[0] && data.user.roles[0].name === 'provider');
                            var modalId = isProvider ? '#editProviderModal' : '#editUserModal';
                            var modal = qs(modalId);
                            
                            console.log('Opening modal:', modalId, 'Found:', !!modal);
                            
                            if (!modal) {
                                console.error('Modal not found:', modalId);
                                return;
                            }
                            
                            // Prefill form fields
                            var user = data.user || data;
                            if (user) {
                                qsa('input, select', modal).forEach(function(input){
                                    var name = input.name;
                                    if (name && user[name] !== undefined) {
                                        if (input.type === 'checkbox') {
                                            input.checked = user[name];
                                        } else if (input.type === 'hidden' && name === 'user_id') {
                                            input.value = user.id;
                                        } else {
                                            input.value = user[name];
                                        }
                                    }
                                });
                                
                                // Handle role selection
                                var roleSelect = modal.querySelector('[name="role"]');
                                if (roleSelect && user.roles && user.roles[0]) {
                                    roleSelect.value = user.roles[0].name;
                                }
                            }
                            
                            // Show modal
                            try {
                                var bsModal = new bootstrap.Modal(modal);
                                bsModal.show();
                            } catch (e) {
                                console.error('Bootstrap modal error:', e);
                                // Fallback to jQuery if Bootstrap is not available
                                if (window.jQuery) {
                                    jQuery(modal).modal('show');
                                }
                            }
                        }).catch(function(err){
                            console.error('Edit button error:', err);
                            if (window.Swal) Swal.fire('Error', 'Failed to load data for editing.', 'error');
                        });
                });
            });
        }

        function bindToggleActions(ctx){
            if (!window.jQuery) return;
            var $ = window.jQuery;
            
            // Remove existing handlers to prevent duplicates
            $(ctx||document).off('change', '.js-verify-toggle');
            
            $(ctx||document).on('change', '.js-verify-toggle', function(){
                var id = this.getAttribute('data-id');
                var checked = this.checked;
                var $switch = $(this);
                
                console.log('Toggle changed:', id, checked);
                
                fetch('/admin/users/'+id+'/verify', { 
                    method:'POST', 
                    headers:{ 
                        'X-Requested-With':'XMLHttpRequest', 
                        'X-CSRF-TOKEN': (qs('meta[name="csrf-token"]')||{}).content,
                        'Content-Type': 'application/json'
                    }, 
                    body: JSON.stringify({ verify: checked ? 1 : 0 })
                })
                .then(r=>r.json()).then(function(data){
                    console.log('Toggle response:', data);
                    if (data.success) {
                        if (window.Swal) { 
                            Swal.fire({ 
                                icon:'success', 
                                title: checked?'Verified':'Unverified', 
                                timer: 1200, 
                                showConfirmButton:false 
                            }); 
                        }
                        // Update title tooltip immediately
                        $switch.closest('.form-check').attr('title', checked?'Verified':'Unverified');
                        
                        // Reload the DataTable
                        var $table = $switch.closest('table');
                        if ($.fn.dataTable.isDataTable($table)) {
                            $table.DataTable().ajax.reload(null, false);
                        }
                    } else {
                        // Revert switch on error
                        $switch.prop('checked', !checked);
                        if (window.Swal) Swal.fire('Error', data.message || 'Failed to update status.', 'error');
                    }
                }).catch(function(err){
                    console.error('Toggle error:', err);
                    $switch.prop('checked', !checked);
                    if (window.Swal) Swal.fire('Error', 'Failed to update status.', 'error');
                });
            });
        }

        function bindDeleteButtons(ctx){
            qsa('.js-delete, .delete-user, .delete-provider', ctx).forEach(function(btn){
                if (btn.getAttribute('data-bound')) return;
                btn.setAttribute('data-bound','1');
                
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    var deleteUrl = btn.getAttribute('data-delete-url') || btn.getAttribute('href');
                    var itemId = btn.getAttribute('data-id');
                    
                    console.log('Delete button clicked:', deleteUrl);
                    
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch(deleteUrl, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': qs('meta[name="csrf-token"]').content
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    console.log('Delete response:', data);
                                    if (data.success) {
                                        // Reload the DataTable
                                        var $table = $(btn).closest('table');
                                        if ($.fn.dataTable.isDataTable($table)) {
                                            $table.DataTable().ajax.reload();
                                        }
                                        Swal.fire('Deleted!', data.message || 'Item deleted successfully!', 'success');
                                    } else {
                                        Swal.fire('Error', data.message || 'Error deleting item.', 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete error:', error);
                                    Swal.fire('Error', 'An error occurred while deleting.', 'error');
                                });
                            }
                        });
                    }
                });
            });
        }

        function rebindAll(ctx){
            console.log('Rebinding all admin components in context:', ctx);
            reinitDataTables(ctx);
            bindAjaxForms(ctx);
            bindEditButtons(ctx);
            bindToggleActions(ctx);
            bindDeleteButtons(ctx);
            // Auto-mark modal forms for AJAX if not already
            qsa('.modal form', ctx).forEach(function(f){ if (!f.getAttribute('data-ajax-submit')) f.setAttribute('data-ajax-submit','1'); });
        }

        // Expose rebindAll globally for external use
        window.AdminPanel.rebindAll = rebindAll;
        
        // Also expose individual functions for debugging
        window.AdminPanel.reinitDataTables = reinitDataTables;
        window.AdminPanel.bindAjaxForms = bindAjaxForms;
        window.AdminPanel.bindEditButtons = bindEditButtons;
        window.AdminPanel.bindToggleActions = bindToggleActions;
        window.AdminPanel.bindDeleteButtons = bindDeleteButtons;

        // PJAX-like navigation for admin sidebar/menu
        document.addEventListener('click', function(e){
            var a = e.target.closest('a');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.startsWith('#') || a.hasAttribute('data-no-ajax')) return;
            // Only hijack internal admin links
            var isInternal = href.indexOf(window.location.origin) === 0 || href.startsWith('/admin');
            if (!isInternal) return;
            // Open in same context
            e.preventDefault();
            if (BUSY) return;
            BUSY = true;
            fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function(r){
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status);
                    }
                    return r.text();
                })
                .then(function(html){
                    // Extract main content between <main> tags
                    var div = document.createElement('div');
                    div.innerHTML = html;
                    var incoming = div.querySelector('main');
                    var currentMain = document.querySelector('main');
                    if (incoming && currentMain){
                        currentMain.innerHTML = incoming.innerHTML;
                        window.history.pushState({}, '', href);
                        document.dispatchEvent(new CustomEvent('ajaxPageLoaded'));
                        rebindAll(currentMain);
                        if (window.Swal) { /* Optional transition */ }
                    } else {
                        // Fallback
                        window.location.href = href;
                    }
                })
                .catch(function(err){
                    console.error('AJAX navigation failed:', err);
                    // Fallback to regular navigation on error
                    window.location.href = href;
                })
                .finally(function(){ BUSY = false; });
        });

        // Provide a global Discount modal opener to eliminate errors on first click
                    window.initializeDiscountModal = window.initializeDiscountModal || function(id){
            var target = '#discountModal';
            var modal = qs(target);
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'discountModal';
                modal.className = 'modal fade';
                modal.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Discount Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="p-3 text-center text-muted">Loading...</div></div></div></div>';
                document.body.appendChild(modal);
            }
            
            var url = id ? '/admin/discount-codes/' + id + '/edit' : '/admin/discount-codes/create';
            fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
                .then(r=>r.text()).then(function(html){
                    var body = modal.querySelector('.modal-body');
                    body.innerHTML = html;
                    rebindAll(body);
                    try {
                        var bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                    } catch (e) {
                        console.error('Bootstrap modal error:', e);
                        if (window.jQuery) jQuery(modal).modal('show');
                    }
                }).catch(function(err){
                    console.error('Discount modal error:', err);
                    if (window.Swal) Swal.fire('Error','Unable to open discount modal','error');
                });
        };

        // Initial bind
        document.addEventListener('DOMContentLoaded', function(){ rebindAll(document); });
        document.addEventListener('ajaxPageLoaded', function(){ rebindAll(document); });
    })();
    </script>
</body>
</html>
