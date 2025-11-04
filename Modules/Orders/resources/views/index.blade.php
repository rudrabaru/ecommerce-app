<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Orders</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal" data-action="create">
                    <i class="fas fa-plus"></i> Create Order
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="orders-table" class="table table-hover" width="100%"
                        data-dt-url="{{ auth()->user()->hasRole('admin') ? route('admin.orders.data') : route('provider.orders.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                        <thead class="table-light">
                            <tr>
                                <th data-column="id" data-width="60px">ID</th>
                                <th data-column="order_number">Order Number</th>
                                <th data-column="customer_name">Customer</th>
                                <th data-column="products" data-orderable="false">Products</th>
                                <th data-column="total" data-width="100px">Total</th>
                                <th data-column="order_status" data-width="100px">Status</th>
                                <th data-column="shipping_address">Shipping Address</th>
                                <th data-column="notes">Notes</th>
                                <th data-column="discount_code">Discount Code</th>
                                <th data-column="discount_amount">Discount Amount</th>
                                <th data-column="created_at" data-width="150px">Created At</th>
                                <th data-column="actions" data-orderable="false" data-searchable="false" data-width="150px">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Create Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm" method="POST" action="{{ auth()->user()->hasRole('admin') ? route('admin.orders.store') : route('provider.orders.store') }}" data-ajax-submit="1" data-reload-table="orders-table">
                        @csrf
                        <input type="hidden" id="orderId" name="order_id">
                        <input type="hidden" name="_method" id="orderMethod" value="POST">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach(\App\Models\User::whereHas('roles', function($q) { $q->where('name', 'user'); })->get() as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_status" class="form-label">Order Status</label>
                                    <select class="form-select" id="order_status" name="order_status">
                                        <option value="pending">Pending</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Items -->
                        <div class="mb-3">
                            <label class="form-label">Items <span class="text-danger">*</span></label>
                            <div id="orderItemsContainer" class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width:60%">Product</th>
                                            <th style="width:20%">Qty</th>
                                            <th style="width:15%">Subtotal</th>
                                            <th style="width:5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderItemsBody"></tbody>
                                </table>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn"><i class="fas fa-plus"></i> Add item</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount_code" class="form-label">Discount Code</label>
                                    <select class="form-select" id="discount_code" name="discount_code">
                                        <option value="">No discount</option>
                                    </select>
                                    <div class="form-text">Select a discount to apply. Eligibility updates as items change.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount_amount_display" class="form-label">Discount Amount</label>
                                    <div id="discount_amount_display" class="fw-bold">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Items Total</label>
                                    <div id="order_total_display" class="fw-bold">-</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Final Total</label>
                                    <div id="final_total_display" class="fw-bold">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveOrderBtn" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="orderSaveSpinner" role="status" aria-hidden="true"></span>
                        Save Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Modal (for item-level status management) -->
    <div class="modal fade" id="orderItemsModal" tabindex="-1" aria-labelledby="orderItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderItemsModalLabel">Order Items - #<span id="orderItemsModalOrderNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Order Status:</strong> 
                        <span id="orderItemsModalOrderStatus" class="badge rounded-pill"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="orderItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Provider</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orderItemsTableBody">
                                <!-- Items will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function(){
            // Ensure function is available on window immediately
            window.openOrderModal = window.openOrderModal || function() {};
            
            // Load products & discounts via AJAX instead of pre-loading
            var PRODUCTS = [];
            var DISCOUNTS = [];
            var ELIGIBLE_DISCOUNTS = [];
            var PROVIDER_PREFILL_DISCOUNT = null; // if set, provider modal uses this discount amount from server

            // Load products and discounts via AJAX
            function loadModalData() {
                return new Promise(function(resolve, reject) {
                    const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                    
                    // Load products
                    fetch(`/${prefix}/orders/modal-data`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            PRODUCTS = data.products || [];
                            DISCOUNTS = data.discounts || [];
                            resolve();
                        } else {
                            reject(new Error('Failed to load modal data'));
                        }
                    })
                    .catch(error => {
                        console.error('Error loading modal data:', error);
                        // Fallback: load products/discounts directly
                        loadProductsAndDiscounts().then(resolve).catch(reject);
                    });
                });
            }

            // Fallback: Load products and discounts separately (if modal-data fails)
            function loadProductsAndDiscounts() {
                return new Promise(function(resolve, reject) {
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                
                    // Load products via DataTables endpoint
                    fetch(`/${prefix}/products/data`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Extract products from DataTables response
                        if (data.data && Array.isArray(data.data)) {
                            PRODUCTS = data.data.map(function(p) {
                                return {
                                    id: p.id,
                                    title: p.title || p.name,
                                    price: parseFloat(p.price) || 0,
                                    category_id: p.category_id || null,
                                    provider_id: p.provider_id || null
                                };
                            });
                        }
                        
                        // Load discounts (admin only)
                        if (window.location.pathname.includes('/admin/')) {
                            return fetch('/admin/discount-codes/data', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.json())
                            .then(data => {
                                // Extract discounts from DataTables response
                                if (data.data && Array.isArray(data.data)) {
                                    DISCOUNTS = data.data
                                        .filter(function(d) { return d.is_active === 'Active'; })
                                        .map(function(d) {
                                            return {
                                                id: d.id,
                                                code: d.code,
                                                discount_type: d.discount_type,
                                                discount_value: parseFloat(d.discount_value) || 0,
                                                minimum_order_amount: d.minimum_order_amount ? parseFloat(d.minimum_order_amount) : null,
                                                category_ids: d.category_ids || []
                                            };
                                        });
                                }
                            })
                            .catch(() => {
                                DISCOUNTS = [];
                            });
                        }
                    })
                    .then(() => resolve())
                    .catch(() => {
                        PRODUCTS = [];
                        DISCOUNTS = [];
                        resolve(); // Resolve anyway so modal can still open
                    });
                });
            }

            // DataTable is now initialized globally - no need for custom initialization

            function buildProductOptions(selectedId){
                var html = '<option value="">Select Product</option>';
                PRODUCTS.forEach(function(p){
                    var sel = (selectedId && selectedId == p.id) ? ' selected' : '';
                    var price = (p.price !== undefined) ? p.price : 0;
                    html += '<option value="'+p.id+'" data-price="'+price+'"'+sel+'>'+ (p.title.replace(/'/g, "\'") ) +' - $'+price+'</option>';
                });
                return html;
            }

            function addOrderItemRow(item){
                item = item || {};
                var tbody = document.getElementById('orderItemsBody');
                var tr = document.createElement('tr');
                var productId = item.product_id || '';
                var qty = item.quantity || 1;
                // Always compute subtotal from product price and quantity to avoid mixing in any prior discounts
                var unit = 0;
                if (productId) {
                    var p = PRODUCTS.find(function(x){ return x.id == productId; });
                    unit = p ? (parseFloat(p.price) || 0) : 0;
                }
                var subtotal = unit * (parseInt(qty) || 1);
                tr.innerHTML = '<td><select class="form-select item-product">'+buildProductOptions(productId)+'</select></td><td><input type="number" class="form-control item-qty" min="1" value="'+qty+'"></td><td class="item-subtotal">$'+Number(subtotal).toFixed(2)+'</td><td><button type="button" class="btn btn-link text-danger p-0 remove-item" title="Remove"><i class="fas fa-times"></i></button></td>';
                tbody.appendChild(tr);
                updateSaveEnabled();
                updateDiscountAmountDisplay();
            }

            function renderOrderItems(items){
                var tbody = document.getElementById('orderItemsBody');
                tbody.innerHTML = '';
                // Always show at least one product dropdown row
                if (!items || items.length === 0){ 
                    addOrderItemRow(); 
                    return; 
                }
                items.forEach(function(it){ addOrderItemRow({ product_id: it.product_id, quantity: it.quantity, total: it.total }); });
            }

            function gatherItems(){
                var items = [];
                $('#orderItemsBody tr').each(function(){
                    var pid = parseInt($(this).find('.item-product').val());
                    var qty = parseInt($(this).find('.item-qty').val() || 1);
                    if (pid && qty) items.push({ product_id: pid, quantity: qty });
                });
                return items;
            }

            function computeTotals(){
                var items = gatherItems();
                var originalTotal = 0;
                var detailed = items.map(function(it){
                    var p = PRODUCTS.find(function(x){ return x.id == it.product_id; });
                    if (!p) return null;
                    var line = (parseFloat(p.price)||0) * (parseInt(it.quantity)||1);
                    originalTotal += line;
                    return { line: line, category_id: p.category_id };
                }).filter(Boolean);

                var selectedCode = $('#discount_code').val();
                var discount = null;
                if (window.location.pathname.includes('/admin/')) {
                    discount = ELIGIBLE_DISCOUNTS.find(function(d){ return d.code == selectedCode; });
                } else {
                    discount = DISCOUNTS.find(function(d){ return d.code == selectedCode; });
                }

                var discountAmount = 0;
                // If provider has a prefilled server discount, prefer it (keeps modal consistent with list)
                if (window.location.pathname.includes('/provider/') && PROVIDER_PREFILL_DISCOUNT !== null) {
                    discountAmount = parseFloat(PROVIDER_PREFILL_DISCOUNT) || 0;
                } else if (discount) {
                    var cats = (discount.category_ids && discount.category_ids.length) ? discount.category_ids : null;
                    var applicableTotal = detailed.filter(function(a){ return cats ? cats.indexOf(a.category_id) !== -1 : true; }).reduce(function(s,a){ return s + a.line; }, 0);
                    if (applicableTotal > 0) {
                        var isPercent = (discount.discount_type === 'percent' || discount.discount_type === 'percentage');
                        if (isPercent) {
                            discountAmount = applicableTotal * (discount.discount_value/100);
                        } else {
                            discountAmount = Math.min(discount.discount_value, applicableTotal);
                        }
                    }
                }

                var finalTotal = Math.max(0, originalTotal - discountAmount);
                $('#order_total_display').text(originalTotal ? ('$'+originalTotal.toFixed(2)) : '-');
                $('#discount_amount_display').text(discountAmount ? ('$'+discountAmount.toFixed(2)) : '-');
                $('#final_total_display').text(finalTotal ? ('$'+finalTotal.toFixed(2)) : (originalTotal?('$'+originalTotal.toFixed(2)):'-'));
            }

            function updateDiscountAmountDisplay(){
                computeTotals();
            }

            function loadEligibleDiscounts(preferredCode){
                if (!window.location.pathname.includes('/admin/')) { return; }
                var items = gatherItems();
                var sel = $('#discount_code');
                if (!items.length){
                    ELIGIBLE_DISCOUNTS = [];
                    sel.html('<option value="">No discount</option>');
                    computeTotals();
                    return;
                }
                fetch('/admin/orders/eligible-discounts', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    body: JSON.stringify({ items: items })
                }).then(function(r){ return r.json(); }).then(function(res){
                    if (!res || !res.success){ return; }
                    ELIGIBLE_DISCOUNTS = res.discounts || [];
                    var current = sel.val();
                    sel.html('<option value="">No discount</option>');
                    ELIGIBLE_DISCOUNTS.forEach(function(d){
                        var isPercent = (d.discount_type === 'percent' || d.discount_type === 'percentage');
                        var label = d.code + ' - ' + (isPercent ? (d.discount_value+'%') : ('$'+d.discount_value));
                        sel.append('<option value="'+d.code+'">'+label+'</option>');
                    });
                    if (preferredCode && ELIGIBLE_DISCOUNTS.some(function(d){ return d.code === preferredCode; })){
                        sel.val(preferredCode);
                    } else if (current && ELIGIBLE_DISCOUNTS.some(function(d){ return d.code === current; })){
                        sel.val(current);
                    } else {
                        sel.val('');
                    }
                    computeTotals();
                }).catch(function(){ /* silent */ });
            }

            // Bind UI events
            $(document).on('click', '#addItemBtn', function(){ addOrderItemRow(); loadEligibleDiscounts(); });
            $(document).on('click', '.remove-item', function(){ $(this).closest('tr').remove(); updateSaveEnabled(); loadEligibleDiscounts(); computeTotals(); });
            $(document).on('change', '.item-product, .item-qty, #discount_code', function(){
                var tr = $(this).closest('tr');
                var price = parseFloat(tr.find('.item-product option:selected').data('price') || 0);
                var qty = parseInt(tr.find('.item-qty').val() || 1);
                tr.find('.item-subtotal').text('$'+(price*qty).toFixed(2));
                updateSaveEnabled();
                if ($(this).is('#discount_code')){
                    computeTotals();
                } else {
                    loadEligibleDiscounts();
                    // Always recompute totals on product/qty change (provider or admin)
                    computeTotals();
                }
            });

            function updateSaveEnabled(){
                var rows = $('#orderItemsBody tr');
                var ok = rows.length > 0;
                rows.each(function(){
                    var pid = $(this).find('.item-product').val();
                    var qty = $(this).find('.item-qty').val();
                    if (!pid || !qty){ ok = false; return false; }
                });
                var hasUser = !!$('#user_id').val();
                var hasAddress = !!($.trim($('#shipping_address').val()||''));
                $('#saveOrderBtn').prop('disabled', !ok || !hasUser || !hasAddress);
            }

            // Populate discount select on modal open
            function populateDiscounts(){
                var sel = $('#discount_code');
                sel.html('<option value="">No discount</option>');
                if (window.location.pathname.includes('/admin/')){
                    ELIGIBLE_DISCOUNTS = [];
                } else {
                    // Provider: filter discounts by current items' categories (if any)
                    var items = gatherItems();
                    if (items.length){
                        var itemCategoryIds = [];
                        items.forEach(function(it){
                            var p = PRODUCTS.find(function(x){ return x.id == it.product_id; });
                            if (p && p.category_id != null && itemCategoryIds.indexOf(p.category_id) === -1){ itemCategoryIds.push(p.category_id); }
                        });
                        DISCOUNTS.filter(function(d){
                            // empty category_ids => global; otherwise intersects with item categories
                            if (!d.category_ids || d.category_ids.length === 0) return true;
                            return d.category_ids.some(function(cid){ return itemCategoryIds.indexOf(cid) !== -1; });
                        }).forEach(function(d){ var isPercent = (d.discount_type==='percent'||d.discount_type==='percentage'); sel.append('<option value="'+d.code+'">'+d.code+' - '+(isPercent?d.discount_value+'%':'$'+d.discount_value) +'</option>'); });
                    } else {
                        DISCOUNTS.forEach(function(d){ var isPercent = (d.discount_type==='percent'||d.discount_type==='percentage'); sel.append('<option value="'+d.code+'">'+d.code+' - '+(isPercent?d.discount_value+'%':'$'+d.discount_value) +'</option>'); });
                    }
                }
            }

            // Update status dropdown options for CREATE modal (all statuses available)
            function updateStatusDropdownForCreate() {
                var statusSelect = $('#order_status');
                statusSelect.empty();
                statusSelect.append('<option value="pending">Pending</option>');
                statusSelect.append('<option value="shipped">Shipped</option>');
                statusSelect.append('<option value="delivered">Delivered</option>');
                statusSelect.append('<option value="cancelled">Cancelled</option>');
            }
            
            // Update status dropdown options for EDIT modal based on role and current status
            function updateStatusDropdownForEdit(currentStatus) {
                var statusSelect = $('#order_status');
                var isAdmin = window.location.pathname.includes('/admin/');
                var isProvider = window.location.pathname.includes('/provider/');
                
                statusSelect.empty();
                
                // Always show current status first
                var statusLabel = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
                statusSelect.append('<option value="' + currentStatus + '" selected>' + statusLabel + '</option>');
                
                if (isAdmin) {
                    // Admin can transition to any status (but not revert from delivered)
                    var allStatuses = ['pending', 'shipped', 'delivered', 'cancelled'];
                    if (currentStatus !== 'delivered') {
                        allStatuses.forEach(function(status) {
                            if (status !== currentStatus) {
                                var label = status.charAt(0).toUpperCase() + status.slice(1);
                                statusSelect.append('<option value="' + status + '">' + label + '</option>');
                            }
                        });
                    }
                } else if (isProvider) {
                    // Provider transitions: pending → shipped/delivered/cancelled, shipped → delivered
                    if (currentStatus === 'pending') {
                        statusSelect.append('<option value="shipped">Shipped</option>');
                        statusSelect.append('<option value="delivered">Delivered</option>');
                        statusSelect.append('<option value="cancelled">Cancelled</option>');
                    } else if (currentStatus === 'shipped') {
                        statusSelect.append('<option value="delivered">Delivered</option>');
                    }
                    // No additional options for 'delivered' or 'cancelled' - they are terminal states
                }
            }

            // Open modal function
            window.openOrderModal = function(orderId = null) {
                $('#orderForm')[0].reset();
                $('.form-control').removeClass('is-invalid');
                
                if (orderId) {
                    // Edit mode
                    $('#orderModalLabel').text('Edit Order');
                    $('#orderMethod').val('PUT');
                    $('#orderId').val(orderId);
                    
                    const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                    fetch(`/${prefix}/orders/${orderId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(o => {
                        if (o.user_id) $('#user_id').val(o.user_id);
                        if (o.order_status) {
                            $('#order_status').val(o.order_status);
                            updateStatusDropdownForEdit(o.order_status);
                        }
                        if (o.shipping_address) $('#shipping_address').val(o.shipping_address);
                        if (o.notes) $('#notes').val(o.notes);
                        
                        const rawItems = (o.order_items || o.orderItems || []);
                        let items = rawItems.map(function(it){ 
                            return { 
                                product_id: it.product_id, 
                                quantity: it.quantity, 
                                total: it.total, 
                                line_discount: (it.line_discount || 0) 
                            }; 
                        });
                        
                        if (window.location.pathname.includes('/provider/')) {
                            items = items.filter(function(it){ 
                                return PRODUCTS.some(function(p){ return p.id == it.product_id; }); 
                            });
                        }
                        
                        renderOrderItems(items);
                        populateDiscounts();
                        
                        if (window.location.pathname.includes('/admin/')) {
                            setTimeout(function(){
                                loadEligibleDiscounts(o.discount_code || '');
                            }, 0);
                        } else {
                            const providerDiscount = items.reduce(function(s,it){ 
                                return s + (parseFloat(it.line_discount)||0); 
                            }, 0);
                            PROVIDER_PREFILL_DISCOUNT = providerDiscount;
                            const providerHasDiscount = providerDiscount > 0;
                            $('#discount_code').val(providerHasDiscount ? (o.discount_code || '') : '');
                            computeTotals();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading order:', error);
                        if (window.Swal) Swal.fire('Error', 'Failed to load order data', 'error');
                    });
                } else {
                    // Create mode
                    $('#orderModalLabel').text('Create Order');
                    $('#orderMethod').val('POST');
                    $('#orderId').val('');
                    
                    // Ensure products are loaded before rendering items
                    if (PRODUCTS.length === 0) {
                        loadModalData().then(function() {
                            renderOrderItems([]);
                            populateDiscounts();
                            loadEligibleDiscounts();
                            PROVIDER_PREFILL_DISCOUNT = null;
                            updateStatusDropdownForCreate();
                        });
                    } else {
                        renderOrderItems([]);
                        populateDiscounts();
                        loadEligibleDiscounts();
                        PROVIDER_PREFILL_DISCOUNT = null;
                        updateStatusDropdownForCreate();
                    }
                }
            };

            // Initialize modal behavior
            document.addEventListener('DOMContentLoaded', function() {
                const orderModal = document.getElementById('orderModal');
                if (orderModal) {
                    orderModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        if (button) {
                            // Check if create button (has data-action="create")
                            if (button.dataset.action === 'create') {
                                openOrderModal(null);
                            } else {
                                // Edit button - get order ID from data-id attribute or onclick
                                const orderId = button.getAttribute('data-id');
                                if (orderId) {
                                    // openOrderModal is already called via onclick, but we ensure it's set
                                    // The onclick handler will handle the actual opening
                                }
                            }
                        }
                    });
                }
                
                // Load modal data on page load
                loadModalData();
                if (window.bindCrudModal) { window.bindCrudModal('orderModal', function(){ openOrderModal(null); }); }
            });

            // Re-initialize on AJAX page load
            window.addEventListener('ajaxPageLoaded', function() {
                const orderModal = document.getElementById('orderModal');
                if (orderModal) {
                    orderModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        if (button) {
                            if (button.dataset.action === 'create') {
                                openOrderModal(null);
                            }
                        }
                    });
                }
                
                // Reload modal data
                loadModalData();
                if (window.bindCrudModal) { window.bindCrudModal('orderModal', function(){ openOrderModal(null); }); }
            });

            // Save handler
            window.saveOrder = function(){
                var form = document.getElementById('orderForm');
                var formData = new FormData(form);
                var items = gatherItems();
                formData.append('items_json', JSON.stringify(items));
                var id = $('#orderId').val();
                var prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                var url = '/'+prefix+'/orders' + (id ? '/'+id : '');
                // If updating ensure method override
                if (id) formData.set('_method','PUT');
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).then(function(r){ return r.json(); }).then(function(data){
                    if (data.success){
                        var modalEl = document.getElementById('orderModal');
                        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        
                        // Reload DataTable using global function
                        window.reloadDataTable('orders-table');
                        
                        Swal.fire('Success', data.message || 'Order saved', 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Validation error', 'error');
                    }
                }).catch(function(){ Swal.fire('Error','An error occurred','error'); });
            };

            // wire up save button to form submit
            $('#saveOrderBtn').on('click', function(){ $('#orderForm').submit(); });

            // ensure form submission triggers saveOrder
            $('#orderForm').on('submit', function(e){
                e.preventDefault();
                updateSaveEnabled();
                if ($('#saveOrderBtn').prop('disabled')){
                    Swal.fire('Missing information', 'Please select a customer, add items, and fill shipping address.', 'warning');
                    return;
                }
                window.saveOrder();
            });

            // View order items modal
            $(document).on('click', '.view-order-items', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                
                fetch('/' + prefix + '/orders/' + orderId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(order => {
                    $('#orderItemsModalOrderNumber').text(order.order_number);
                    $('#orderItemsModalOrderStatus').text(order.order_status).attr('class', 'badge rounded-pill ' + getStatusBadgeClass(order.order_status));
                    
                    const tbody = $('#orderItemsTableBody');
                    tbody.empty();
                    
                    const items = order.order_items || order.orderItems || [];
                    const isProvider = window.location.pathname.includes('/provider/');
                    const currentUserId = {{ auth()->id() }};
                    
                    items.forEach(function(item) {
                        // Filter items for provider view
                        if (isProvider && item.provider_id !== currentUserId) {
                            return;
                        }
                        
                        // Admin can update item statuses in modal, providers can update their own items
                        const allowedTransitions = item.allowed_transitions || [];
                        let statusDropdown = '<span class="badge ' + getStatusBadgeClass(item.order_status) + '">' + item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1) + '</span>';
                        
                        // Show status dropdown for admin OR for provider (provider can update their own items in modal)
                        if (!isProvider && allowedTransitions.length > 0) {
                            // Admin: show dropdown in modal
                            statusDropdown = '<div class="btn-group">';
                            statusDropdown += '<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">';
                            statusDropdown += item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1) + '</button>';
                            statusDropdown += '<ul class="dropdown-menu">';
                            allowedTransitions.forEach(function(status) {
                                statusDropdown += '<li><a class="dropdown-item update-item-status-btn" href="#" data-order-id="' + orderId + '" data-item-id="' + item.id + '" data-status="' + status + '">';
                                statusDropdown += status.charAt(0).toUpperCase() + status.slice(1) + '</a></li>';
                            });
                            statusDropdown += '</ul></div>';
                        } else if (isProvider && allowedTransitions.length > 0 && item.provider_id === currentUserId) {
                            // Provider: can update their own items in modal
                            statusDropdown = '<div class="btn-group">';
                            statusDropdown += '<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">';
                            statusDropdown += item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1) + '</button>';
                            statusDropdown += '<ul class="dropdown-menu">';
                            allowedTransitions.forEach(function(status) {
                                statusDropdown += '<li><a class="dropdown-item update-item-status-btn" href="#" data-order-id="' + orderId + '" data-item-id="' + item.id + '" data-status="' + status + '">';
                                statusDropdown += status.charAt(0).toUpperCase() + status.slice(1) + '</a></li>';
                            });
                            statusDropdown += '</ul></div>';
                        }
                        
                        const row = '<tr>' +
                            '<td>' + (item.product ? item.product.title : 'Product') + '</td>' +
                            '<td>' + (item.provider ? item.provider.name : 'N/A') + '</td>' +
                            '<td>' + item.quantity + '</td>' +
                            '<td> + parseFloat(item.unit_price).toFixed(2) + '</td>' +
                            '<td> + parseFloat(item.total).toFixed(2) + '</td>' +
                            '<td>' + statusDropdown + '</td>' +
                            '<td></td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                    
                    if (tbody.children().length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center text-muted">No items found</td></tr>');
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('orderItemsModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to load order items', 'error');
                });
            });
            
            function getStatusBadgeClass(status) {
                const map = {
                    'pending': 'bg-warning',
                    'shipped': 'bg-primary',
                    'delivered': 'bg-success',
                    'cancelled': 'bg-danger'
                };
                return map[status] || 'bg-secondary';
            }
            
            // Item status update handler
            $(document).on('click', '.update-item-status-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('order-id');
                const itemId = $(this).data('item-id');
                const newStatus = $(this).data('status');
                const btn = $(this);
                const originalText = btn.text();
                
                btn.text('Updating...').prop('disabled', true);
                
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                const url = '/' + prefix + '/orders/' + orderId + '/items/' + itemId + '/update-status';
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update modal content
                        if ($('#orderItemsModal').hasClass('show')) {
                            $('.view-order-items[data-id="' + orderId + '"]').click();
                        }
                        
                        // Reload DataTable using global function
                        window.reloadDataTable('orders-table');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: data.message || 'Order item status updated successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update status', 'error');
                        btn.text(originalText).prop('disabled', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred while updating status', 'error');
                    btn.text(originalText).prop('disabled', false);
                });
            });

            // Status update handler (order-level)
            $(document).on('click', '.update-status-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('order-id');
                const newStatus = $(this).data('status');
                const btn = $(this);
                const originalText = btn.text();
                
                btn.text('Updating...').prop('disabled', true);
                
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                const url = '/' + prefix + '/orders/' + orderId + '/update-status';
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload DataTable using global function
                        window.reloadDataTable('orders-table');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: data.message || 'Order status updated successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update status', 'error');
                        btn.text(originalText).prop('disabled', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred while updating status', 'error');
                    btn.text(originalText).prop('disabled', false);
                });
            });

            // Initial setup
            $(document).ready(function(){ 
                populateDiscounts(); 
                updateSaveEnabled(); 
                computeTotals(); 
            });
        })();
    </script>
    @endpush
</x-app-layout>