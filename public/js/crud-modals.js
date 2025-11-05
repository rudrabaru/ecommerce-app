// crud-modals.js
(function(){
    // Map modules to modal IDs and open functions
    var MODULES = {
        users:      { modalId: 'userModal',       openFn: 'openUserModal',       tableId: 'users-table' },
        providers:  { modalId: 'providerModal',   openFn: 'openProviderModal',   tableId: 'providers-table' },
        products:   { modalId: 'productModal',    openFn: 'openProductModal',    tableId: 'products-table' },
        categories: { modalId: 'categoryModal',   openFn: 'openCategoryModal',   tableId: 'categories-table' },
        discounts:  { modalId: 'discountModal',   openFn: 'openDiscountModal',   tableId: 'discounts-table' },
        orders:     { modalId: 'orderModal',      openFn: 'openOrderModal',      tableId: 'orders-table' },
        payments:   { modalId: 'paymentModal',    openFn: 'openPaymentModal',    tableId: 'payments-table' }
    };

    function capitalize(s){ return (s||'').charAt(0).toUpperCase() + (s||'').slice(1); }

    function showModal(mod){
        var el = document.getElementById(mod.modalId);
        if (!el) return;
        var modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    }

    // Delegated CREATE
    $(document).on('click', '.createBtn', function(e){
        e.preventDefault();
        var moduleKey = $(this).data('module');
        var mod = MODULES[moduleKey];
        if (!mod) return;
        var fn = window[mod.openFn];
        if (typeof fn !== 'function') return;
        
        try {
            var result = fn(null);
            
            // Check if function returns a promise
            if (result && typeof result.then === 'function') {
                // Wait for async operation (e.g., loading products for orders)
                result.then(function(success) {
                    if (success !== false) {
                        showModal(mod);
                    }
                }).catch(function(err) {
                    console.error('Error opening create modal:', err);
                });
            } else {
                // For sync modules, show immediately
                showModal(mod);
            }
        } catch(err){ 
            console.error('Error opening create modal:', err); 
        }
    });

    // Delegated EDIT
    $(document).on('click', '.editBtn', function(e){
        e.preventDefault();
        var moduleKey = $(this).data('module');
        var id = $(this).data('id');
        var mod = MODULES[moduleKey];
        if (!mod || !id) return;
        var fn = window[mod.openFn];
        if (typeof fn !== 'function') return;
        
        try {
            var result = fn(id);
            
            // Check if function returns a promise (async modules)
            if (result && typeof result.then === 'function') {
                // Wait for async operation to complete before showing modal
                result.then(function(success) {
                    if (success !== false) {
                        showModal(mod);
                    }
                }).catch(function(err) {
                    console.error('Error loading data for edit modal:', err);
                });
            } else {
                // For sync modules, show immediately
                showModal(mod);
            }
        } catch(err){ 
            console.error('Error opening edit modal:', err); 
        }
    });

    // Map module keys to function name suffixes (handles plural → singular)
    var FUNCTION_NAMES = {
        users: 'User',
        providers: 'Provider',
        products: 'Product',
        categories: 'Category',
        discounts: 'Discount',
        orders: 'Order',
        payments: 'Payment'
    };

    // Delegated SAVE - handles form submission via AJAX
    $(document).on('click', '.saveBtn', function(e){
        e.preventDefault();
        var moduleKey = $(this).data('module');
        if (!moduleKey) {
            // Try to infer from modal ID if data-module not set
            var $btn = $(this);
            var $modal = $btn.closest('.modal').length ? $btn.closest('.modal') : $($btn.closest('.modal-content').closest('.modal'));
            var modalId = $modal.length ? $modal.attr('id') : null;
            if (modalId) {
                for (var key in MODULES) {
                    if (MODULES[key].modalId === modalId) {
                        moduleKey = key;
                        break;
                    }
                }
            }
        }
        if (!moduleKey) return;
        
        var functionSuffix = FUNCTION_NAMES[moduleKey] || capitalize(moduleKey);
        var saveFn = window['save' + functionSuffix];
        if (typeof saveFn !== 'function') {
            console.error('Save function not found for module:', moduleKey, 'expected: save' + functionSuffix);
            return;
        }
        
        try {
            saveFn();
        } catch(err) {
            console.error('Error saving ' + moduleKey + ':', err);
        }
    });

    // Delegated DELETE - handles delete confirmation and AJAX deletion
    $(document).on('click', '.deleteBtn', function(e){
        e.preventDefault();
        var moduleKey = $(this).data('module');
        var id = $(this).data('id');
        if (!moduleKey || !id) return;
        
        var functionSuffix = FUNCTION_NAMES[moduleKey] || capitalize(moduleKey);
        var deleteFn = window['delete' + functionSuffix];
        if (typeof deleteFn !== 'function') {
            console.error('Delete function not found for module:', moduleKey, 'expected: delete' + functionSuffix);
            return;
        }
        
        try {
            deleteFn(id);
        } catch(err) {
            console.error('Error deleting ' + moduleKey + ':', err);
        }
    });
})();