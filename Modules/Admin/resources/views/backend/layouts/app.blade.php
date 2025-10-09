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

    @if (!empty(config('settings.global_custom_js')))
    
    @endif

    @livewireScriptConfig

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_FOOTER_AFTER, '') !!}

    <script>
    (function(){
        // Centralized AJAX navigation + DataTables + modal rebinds for Admin UI
        var BUSY = false;

        function qs(sel, root){ return (root||document).querySelector(sel); }
        function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

        function reinitDataTables(ctx){
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable) return;
            var $ = window.jQuery;
            window.DataTableInstances = window.DataTableInstances || {};
            qsa('table[data-dt-url]', ctx).forEach(function(el){
                var id = el.id || ('dt-' + Math.random().toString(36).slice(2));
                el.id = id;
                var $table = $(el);
                if ($.fn.dataTable.isDataTable($table)) return;
                var orderAttr = $table.attr('data-dt-order');
                var order = [];
                try { order = orderAttr ? JSON.parse(orderAttr) : []; } catch(e) {}
                window.DataTableInstances[id] = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: $table.data('dt-url'),
                    pageLength: $table.data('dt-page-length') || 25,
                    order: order
                });
            });
        }

        function bindAjaxForms(ctx){
            var $ = window.jQuery;
            qsa('form[data-ajax-submit="1"]', ctx).forEach(function(form){
                var bound = form.getAttribute('data-bound');
                if (bound) return;
                form.setAttribute('data-bound','1');
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(form);
                    fetch(form.action, {
                        method: form.method || 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': (qs('meta[name="csrf-token"]')||{}).content },
                        body: fd
                    }).then(r=>r.json()).then(function(res){
                        if (res && res.success){
                            if (window.Swal) { Swal.fire({ icon:'success', title: res.message||'Saved', timer: 1400, showConfirmButton:false }); }
                            // Reload related DataTable if provided
                            if (res.reload_table_id && window.jQuery && window.DataTableInstances && DataTableInstances[res.reload_table_id]){
                                jQuery('#'+res.reload_table_id).DataTable().ajax.reload(null,false);
                            } else if (window.jQuery){
                                // Fallback: reload first visible table
                                var t = jQuery('table[data-dt-url]').first(); if (t.length) t.DataTable().ajax.reload(null,false);
                            }
                            // Close modal
                            var modal = form.closest('.modal'); if (modal && window.jQuery) jQuery(modal).modal('hide');
                        } else if (res && res.errors){
                            // Show inline validation
                            Object.keys(res.errors).forEach(function(name){
                                var input = form.querySelector('[name="'+name+'"]');
                                if (!input) return;
                                input.classList.add('is-invalid');
                                var fb = input.nextElementSibling;
                                if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = (res.errors[name][0]||'');
                            });
                            if (window.Swal) { Swal.fire({ icon:'error', title:'Validation error' }); }
                        }
                    });
                });
            });
        }

        function bindEditButtons(ctx){
            // Buttons/links with data-edit-url or plain href (edit endpoints should return JSON on XHR)
            qsa('[data-edit-url], a.btn-edit, button.btn-edit', ctx).forEach(function(btn){
                if (btn.getAttribute('data-bound')) return;
                btn.setAttribute('data-bound','1');
                btn.addEventListener('click', function(){
                    var url = btn.getAttribute('data-edit-url') || btn.getAttribute('href');
                    var target = btn.getAttribute('data-target') || '#editModal';
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(r=>r.json()).then(function(data){
                            var modal = qs(target);
                            if (!modal) return;
                            Object.keys(data||{}).forEach(function(k){
                                var el = modal.querySelector('[name="'+k+'"]');
                                if (!el) return;
                                if (el.tagName==='SELECT' || el.tagName==='INPUT' || el.tagName==='TEXTAREA') el.value = data[k];
                            });
                            if (window.jQuery) jQuery(modal).modal('show');
                        });
                });
            });
        }

        function bindToggleActions(ctx){
            if (!window.jQuery) return;
            var $ = window.jQuery;
            $(ctx||document).off('change', '.js-verify-toggle').on('change', '.js-verify-toggle', function(){
                var id = this.getAttribute('data-id');
                var checked = this.checked;
                fetch('/admin/users/'+id+'/verify', { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': (qs('meta[name="csrf-token"]')||{}).content }, body: new URLSearchParams({ verified: checked?1:0 }) })
                    .then(r=>r.json()).then(function(){
                        if (window.Swal) { Swal.fire({ icon:'success', title: checked?'Verified':'Unverified', timer: 1200, showConfirmButton:false }); }
                        // Update title tooltip immediately
                        try { $(ctx||document).find('.js-verify-toggle[data-id="'+id+'"]').closest('.form-check').attr('title', checked?'Verified':'Unverified'); } catch(e) {}
                        // Reload the enclosing DataTable row
                        try {
                            var $table = $(ctx||document).find(this).closest('table');
                        } catch(e) {}
                        // Find first DataTable and reload
                        if (window.DataTableInstances && $table && $table.length && $.fn.dataTable.isDataTable($table)) {
                            $table.DataTable().ajax.reload(null,false);
                        } else if (window.jQuery) {
                            var t = jQuery('table[data-dt-url]').first(); if (t.length) t.DataTable().ajax.reload(null,false);
                        }
                    });
            });
        }

        function rebindAll(ctx){
            reinitDataTables(ctx);
            bindAjaxForms(ctx);
            bindEditButtons(ctx);
            bindToggleActions(ctx);
            // Auto-mark modal forms for AJAX if not already
            qsa('.modal form', ctx).forEach(function(f){ if (!f.getAttribute('data-ajax-submit')) f.setAttribute('data-ajax-submit','1'); });
        }

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
                .then(r=>r.text())
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
                .finally(function(){ BUSY = false; });
        });

        // Provide a global Discount modal opener to eliminate errors on first click
        window.openDiscountModal = window.openDiscountModal || function(url){
            var target = '#discountModal';
            var modal = qs(target);
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'discountModal';
                modal.className = 'modal fade';
                modal.innerHTML = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Discount Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="p-3 text-center text-muted">Loading...</div></div></div></div>';
                document.body.appendChild(modal);
            }
            fetch(url || '/admin/discount-codes/create', { headers: { 'X-Requested-With':'XMLHttpRequest' }})
                .then(r=>r.text()).then(function(html){
                    var body = modal.querySelector('.modal-body');
                    body.innerHTML = html;
                    rebindAll(body);
                    if (window.jQuery) jQuery(modal).modal('show');
                }).catch(function(){ if (window.Swal) Swal.fire('Error','Unable to open discount modal','error'); });
        };

        // Initial bind
        document.addEventListener('DOMContentLoaded', function(){ rebindAll(document); });
        document.addEventListener('ajaxPageLoaded', function(){ rebindAll(document); });
    })();
    </script>
</body>
</html>
