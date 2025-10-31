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
    <link href="{{ asset('css/admin-fixes.css') }}" rel="stylesheet">
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

        // Replace the bindEditButtons function in app.blade.php with this:

        function isLocalOptOut(el){
            try {
                var cur = el;
                while(cur && cur !== document) {
                    if (cur.getAttribute && cur.getAttribute('data-local-modal') !== null) return true;
                    cur = cur.parentNode;
                }
            } catch(e){ }
            return false;
        }

        function bindEditButtons(ctx){
            qsa('[data-action="edit"], [data-action="create"], .btn-edit, .edit-user, .edit-provider, .edit-order, .edit-payment, .edit-product, .edit-category', ctx).forEach(function(btn){
                // No skips: all edit/create buttons use the global modal handler so modals open immediately
                
                // Allow pages to opt-out of global binding to avoid duplication (element or ancestor)
                if (isLocalOptOut(btn)) { console.log('Skipping local-modal element from global binder:', btn); return; }
                if (btn.getAttribute('data-bound')) return;
                btn.setAttribute('data-bound','1');
                
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    
                    var action = btn.getAttribute('data-action') || 'create';
                    var modalId = btn.getAttribute('data-modal') || btn.getAttribute('data-bs-target');
                    var itemId = btn.getAttribute('data-id') || btn.getAttribute('data-user-id') || btn.getAttribute('data-provider-id') || btn.getAttribute('data-order-id') || btn.getAttribute('data-payment-id') || btn.getAttribute('data-product-id') || btn.getAttribute('data-category-id');
                    
                    console.log('Modal button clicked:', {action, modalId, itemId});
                    
                    if (!modalId) {
                        console.error('No modal ID found for button');
                        return;
                    }
                    
                    var modal = qs(modalId);
                    if (!modal) {
                        console.error('Modal not found:', modalId);
                        return;
                    }
                    
                    // Reset form
                    var form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                        qsa('.form-control', modal).forEach(function(input){
                            input.classList.remove('is-invalid');
                        });
                        qsa('.invalid-feedback', modal).forEach(function(feedback){
                            feedback.textContent = '';
                        });
                    }
                    
                    // Set modal title and method
                    var titleElement = modal.querySelector('.modal-title');
                    var methodInput = modal.querySelector('input[name="_method"]');
                    var idInput = modal.querySelector('input[name*="_id"]');
                    
                    if (action === 'edit' && itemId) {
                        if (titleElement) titleElement.textContent = titleElement.textContent.replace('Create', 'Edit');
                        if (methodInput) methodInput.value = 'PUT';
                        if (idInput) idInput.value = itemId;
                        if (form) { form.action = determineSubmitUrl(modalId, itemId); }
                        
                        // Load data for editing
                        var editUrl = determineEditUrl(modalId, itemId);
                        if (editUrl) {
                            // Show modal immediately for responsive UX, then populate
                            try { new bootstrap.Modal(modal).show(); } catch(_){}
                            loadEditData(editUrl, modal);
                        }
                    } else {
                        if (titleElement) titleElement.textContent = titleElement.textContent.replace('Edit', 'Create');
                        if (methodInput) methodInput.value = 'POST';
                        if (idInput) idInput.value = '';
                        if (form) { form.action = determineSubmitUrl(modalId, null); }
                    }
                    
                    // Show modal
                    try {
                        var bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                    } catch (e) {
                        console.error('Bootstrap modal error:', e);
                        if (window.jQuery) {
                            jQuery(modal).modal('show');
                        }
                    }
                });
            });
        }
        
        function determineEditUrl(modalId, itemId) {
            var baseUrl = '';
            if (modalId.includes('user')) {
                baseUrl = '/admin/users/' + itemId + '/edit';
            } else if (modalId.includes('provider')) {
                baseUrl = '/admin/providers/' + itemId + '/edit';
            } else if (modalId.includes('order')) {
                // Support both admin and provider contexts
                var prefix = (window.location.pathname.indexOf('/provider/') !== -1) ? '/provider' : '/admin';
                baseUrl = prefix + '/orders/' + itemId + '/edit';
            } else if (modalId.includes('payment')) {
                baseUrl = '/admin/payments/' + itemId + '/edit';
            } else if (modalId.includes('product')) {
                baseUrl = '/admin/products/' + itemId + '/edit';
            } else if (modalId.includes('category')) {
                baseUrl = '/admin/categories/' + itemId + '/edit';
            } else if (modalId.includes('discount')) {
                baseUrl = '/admin/discount-codes/' + itemId + '/edit';
            }
            return baseUrl;
        }

        function determineSubmitUrl(modalId, itemId){
            if (modalId.includes('discount')) {
                return itemId ? ('/admin/discount-codes/' + itemId) : '/admin/discount-codes';
            } else if (modalId.includes('order')) {
                var prefix = (window.location.pathname.indexOf('/provider/') !== -1) ? '/provider' : '/admin';
                return itemId ? (prefix + '/orders/' + itemId) : (prefix + '/orders');
            }
            return '';
        }
        
        function loadEditData(url, modal) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(r => r.json())
                .then(function(data){
                    console.log('Edit data received:', data);
                    
                    var item = data.user || data.product || data.category || data.order || data.payment || data.discount || data;

                    // Populate discount categories if present
                    if (data.categories && modal.querySelector('[name="category_id"]')) {
                        var selectedId = (item && (item.category_id || (item.categories && item.categories[0] && item.categories[0].id))) || null;
                        populateCategories(modal, data.categories, selectedId);
                    }
                    
                    if (item) {
                        qsa('input, select, textarea', modal).forEach(function(input){
                            var name = input.name;
                            if (name && item[name] !== undefined) {
                                if (input.type === 'checkbox') {
                                    input.checked = item[name];
                                } else if (input.type === 'hidden' && name.includes('_id')) {
                                    input.value = item.id;
                                } else {
                                    input.value = item[name];
                                }
                            }
                        });
                        
                        // Handle special cases
                        var roleSelect = modal.querySelector('[name="role"]');
                        if (roleSelect && item.roles && item.roles[0]) {
                            roleSelect.value = item.roles[0].name;
                        }
                        
                        // Handle order_status vs status
                        var orderStatusSelect = modal.querySelector('[name="order_status"]');
                        if (orderStatusSelect && item.order_status) {
                            orderStatusSelect.value = item.order_status;
                        }
                    }
                })
                .catch(function(err){
                    console.error('Error loading edit data:', err);
                    if (window.Swal) Swal.fire('Error', 'Failed to load data for editing.', 'error');
                });
        }

        function populateCategories(modal, categories, selectedId){
            try {
                var sel = modal.querySelector('#category_ids');
                if (!sel) sel = modal.querySelector('[name="category_id"]');
                if (!sel) return;
                sel.innerHTML = '<option value="">Select Category</option>';
                (categories||[]).forEach(function(c){
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if (selectedId && String(selectedId) === String(c.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
            } catch(e){ console.warn('populateCategories failed', e); }
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
                                title: checked?'Account Approved':'Account Suspended', 
                                text: data.message,
                                timer: 1200, 
                                showConfirmButton:false 
                            }); 
                        }
                        // Update title tooltip immediately
                        $switch.closest('.form-check').attr('title', checked?'Account Approved':'Account Pending');
                        
                        // Reload the DataTable
                        var $table = $switch.closest('table');
                        if ($.fn.dataTable.isDataTable($table)) {
                            $table.DataTable().ajax.reload(null, false);
                        }
                    } else {
                        // Revert switch on error
                        $switch.prop('checked', !checked);
                        if (window.Swal) Swal.fire('Error', data.message || 'Failed to update account status.', 'error');
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
            // Page-scoped modal initializers (ensures inline pattern works after PJAX)
            try { initDiscountModalHandlers(ctx); } catch(e){ console.warn('initDiscountModalHandlers failed', e); }
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

        // Initial bind
        document.addEventListener('DOMContentLoaded', function(){ rebindAll(document); });
        document.addEventListener('ajaxPageLoaded', function(){ rebindAll(document); });
    })();
    </script>
    <script>
    // Discount modals (create + edit) initializer to support PJAX navigation
    (function(){
        function qs(sel, root){ return (root||document).querySelector(sel); }
        function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }
        function onShow(modal, handler){ if (!window.jQuery) return; jQuery(modal).off('show.bs.modal._discount').on('show.bs.modal._discount', handler); }

        function fetchCategories(){
            return fetch('/admin/discount-codes/create', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function(r){ if (!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
                .then(function(data){ return data.categories || []; });
        }
        function buildCategorySelect(categories, selectedId){
            var sel = document.createElement('select'); sel.name='category_ids[]'; sel.className='form-select';
            var o0=document.createElement('option'); o0.value=''; o0.textContent='Select Category'; sel.appendChild(o0);
            categories.forEach(function(c){ var o=document.createElement('option'); o.value=c.id; o.textContent=c.name; if (selectedId && String(selectedId)===String(c.id)) o.selected=true; sel.appendChild(o); });
            return sel;
        }
        function addCategoryRow(container, categories, selectedId){
            var row=document.createElement('div'); row.className='category-row mb-2';
            var flex=document.createElement('div'); flex.className='d-flex gap-2';
            var remove=document.createElement('button'); remove.type='button'; remove.className='btn btn-outline-danger remove-category'; remove.innerHTML='<i class="fas fa-times"></i>';
            flex.appendChild(buildCategorySelect(categories, selectedId)); flex.appendChild(remove); row.appendChild(flex);
            var fb=document.createElement('div'); fb.className='invalid-feedback'; row.appendChild(fb);
            container.appendChild(row);
            container.querySelectorAll('.remove-category').forEach(function(btn){ btn.style.display = (container.querySelectorAll('.category-row').length>1)?'inline-flex':'none'; });
            remove.addEventListener('click', function(){ if (container.querySelectorAll('.category-row').length>1){ row.remove(); container.querySelectorAll('.remove-category').forEach(function(btn){ btn.style.display=(container.querySelectorAll('.category-row').length>1)?'inline-flex':'none'; }); } });
        }

        function wireCreate(){
            var modal = qs('#discountCreateModal'); if (!modal) return;
            onShow(modal, function(){
                var form = qs('#discountCreateForm'); form.reset();
                qsa('.is-invalid', form).forEach(function(el){ el.classList.remove('is-invalid'); });
                qsa('.invalid-feedback', form).forEach(function(el){ el.textContent=''; });
                form.action = '/admin/discount-codes';
                form.querySelector('input[name="_method"]').value='POST';
                var container = qs('#categoriesCreateContainer', modal); container.innerHTML='';
                fetchCategories().then(function(categories){
                    addCategoryRow(container, categories, null);
                    var addBtn = qs('#addCategoryBtnCreate', modal); addBtn.onclick = function(){ addCategoryRow(container, categories, null); };
                }).catch(function(err){ console.error('[Discount] categories load failed', err); });
            });

            window.saveDiscountCreate = function(){
                var form = qs('#discountCreateForm'); var spinner=qs('#discountCreateSpinner'); var btn=qs('#discountCreateSaveBtn');
                if (spinner) spinner.classList.remove('d-none'); if (btn) btn.disabled=true;
                fetch('/admin/discount-codes', { method:'POST', body:new FormData(form), headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]').content) }})
                .then(function(r){ return r.json(); }).then(function(data){ if(!data.success) throw {payload:data}; try{ bootstrap.Modal.getInstance(qs('#discountCreateModal')).hide(); }catch(_){} if (window.DataTableInstances && window.DataTableInstances['discounts-table']) window.DataTableInstances['discounts-table'].ajax.reload(null,false); if (window.Swal) Swal.fire('Success','Saved','success'); })
                .catch(function(err){ var errors=(err&&err.payload&&err.payload.errors)||{}; Object.keys(errors).forEach(function(k){ var el=form.querySelector('[name="'+k+'"]'); if (el){ el.classList.add('is-invalid'); var fb=el.nextElementSibling; if (fb && fb.classList.contains('invalid-feedback')) fb.textContent=errors[k][0]; }}); if (window.Swal) Swal.fire('Error', (err&&err.payload&&err.payload.message)||'Validation failed','error'); })
                .finally(function(){ if (spinner) spinner.classList.add('d-none'); if (btn) btn.disabled=false; });
            };
        }

        function wireEdit(){
            var modal = qs('#discountEditModal'); if (!modal) return;
            onShow(modal, function(e){
                var trigger = e.relatedTarget || {}; var id = trigger.getAttribute ? (trigger.getAttribute('data-id') || trigger.getAttribute('data-discount-id')) : null;
                var form = qs('#discountEditForm'); form.reset(); qsa('.is-invalid', form).forEach(function(el){ el.classList.remove('is-invalid'); }); qsa('.invalid-feedback', form).forEach(function(el){ el.textContent=''; });
                form.action = id ? ('/admin/discount-codes/'+id) : '';
                form.querySelector('input[name="_method"]').value='PUT';
                var container = qs('#categoriesEditContainer', modal); container.innerHTML='';
                Promise.all([
                    fetch('/admin/discount-codes/'+id+'/edit', { headers:{ 'X-Requested-With':'XMLHttpRequest' } }).then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); }),
                    fetchCategories()
                ]).then(function(out){ var data=out[0]||{}; var categories=out[1]||[]; var d=data.discount||{};
                    qsa('input, select, textarea', form).forEach(function(input){ var name=input.name; if (name && d[name]!==undefined){ if (input.type==='checkbox') input.checked=!!d[name]; else input.value=d[name]; }});
                    var vf=form.querySelector('input[name="valid_from"]'); if (vf && d.valid_from) vf.value=String(d.valid_from).replace(' ','T').slice(0,16);
                    var vu=form.querySelector('input[name="valid_until"]'); if (vu && d.valid_until) vu.value=String(d.valid_until).replace(' ','T').slice(0,16);
                    var attached=(d.categories||[]).map(function(c){ return c.id; }); if (attached.length===0) addCategoryRow(container, categories, null); else attached.forEach(function(cid){ addCategoryRow(container, categories, cid); });
                    var addBtn = qs('#addCategoryBtnEdit', modal); addBtn.onclick=function(){ addCategoryRow(container, categories, null); };
                }).catch(function(err){ console.error('[Discount] edit preload failed', err); if (window.Swal) Swal.fire('Error','Failed to load discount','error'); });
            });

            window.saveDiscountEdit = function(){
                var form = qs('#discountEditForm'); var spinner=qs('#discountEditSpinner'); var btn=qs('#discountEditSaveBtn'); var action=form.action;
                if (spinner) spinner.classList.remove('d-none'); if (btn) btn.disabled=true;
                fetch(action, { method:'POST', body:new FormData(form), headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]').content) }})
                .then(function(r){ return r.json(); }).then(function(data){ if(!data.success) throw {payload:data}; try{ bootstrap.Modal.getInstance(qs('#discountEditModal')).hide(); }catch(_){} if (window.DataTableInstances && window.DataTableInstances['discounts-table']) window.DataTableInstances['discounts-table'].ajax.reload(null,false); if (window.Swal) Swal.fire('Success','Saved','success'); })
                .catch(function(err){ var errors=(err&&err.payload&&err.payload.errors)||{}; Object.keys(errors).forEach(function(k){ var el=form.querySelector('[name="'+k+'"]'); if (el){ el.classList.add('is-invalid'); var fb=el.nextElementSibling; if (fb && fb.classList.contains('invalid-feedback')) fb.textContent=errors[k][0]; }}); if (window.Swal) Swal.fire('Error', (err&&err.payload&&err.payload.message)||'Validation failed','error'); })
                .finally(function(){ if (spinner) spinner.classList.add('d-none'); if (btn) btn.disabled=false; });
            };
        }

        window.initDiscountModalHandlers = function(){ wireCreate(); wireEdit(); };
    })();
    </script>
    <script>
    (function(){
        try {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function(){ try { window.initDiscountModalHandlers && window.initDiscountModalHandlers(); } catch(e){ console.warn('initDiscountModalHandlers onload failed', e); } });
            } else {
                try { window.initDiscountModalHandlers && window.initDiscountModalHandlers(); } catch(e){ console.warn('initDiscountModalHandlers immediate failed', e); }
            }
        } catch(e){ console.warn(e); }
    })();
    </script>
</body>
</html>
