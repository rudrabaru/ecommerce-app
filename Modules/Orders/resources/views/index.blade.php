<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Orders</h1>
            <div>
                <!-- Local-only create trigger: remove bootstrap auto-toggle so page-local JS controls the modal opening -->
                <button type="button" class="btn btn-primary" data-action="create" data-local-modal="1">
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
                                        @if(auth()->user()->hasRole('admin'))
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        @elseif(auth()->user()->hasRole('provider'))
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        @endif
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
            // Preload products & discounts as JSON for modal population (fetched server-side at render time)
            @php
                $__orders_modal_products = auth()->user()->hasRole('provider') ? \Modules\Products\Models\Product::where('provider_id', auth()->id())->get() : \Modules\Products\Models\Product::all();
                $__orders_modal_discounts = \App\Models\DiscountCode::active()->validNow()->notExceededUsage()->get();
                // Build light payload with category_ids for provider-side client filtering
                $__orders_modal_discounts_payload = $__orders_modal_discounts->map(function($d){
                    return [
                        'id' => $d->id,
                        'code' => $d->code,
                        'discount_type' => $d->discount_type,
                        'discount_value' => (float) $d->discount_value,
                        'minimum_order_amount' => $d->minimum_order_amount ? (float) $d->minimum_order_amount : null,
                        'category_ids' => $d->categories()->pluck('categories.id')->all(),
                    ];
                });
            @endphp
            var PRODUCTS = {!! $__orders_modal_products->toJson() !!};
            var DISCOUNTS = {!! $__orders_modal_discounts_payload->toJson() !!};
            var ELIGIBLE_DISCOUNTS = [];
            var PROVIDER_PREFILL_DISCOUNT = null; // if set, provider modal uses this discount amount from server

            function initDataTable(){
                if ($('#orders-table').length && !$.fn.DataTable.isDataTable('#orders-table')) {
                    const ajaxUrl = window.location.pathname.includes('/admin/') ? '/admin/orders/data' : '/provider/orders/data';
                    window.DataTableInstances = window.DataTableInstances || {};
                    window.DataTableInstances['orders-table'] = $('#orders-table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: ajaxUrl,
                        columns: [
                            { data: 'id', name: 'id', width: '60px' },
                            { data: 'order_number', name: 'order_number' },
                            { data: 'customer_name', name: 'customer_name' },
                            { data: 'products', name: 'products', orderable: false },
                            { data: 'total', name: 'total', width: '100px' },
                            { data: 'order_status', name: 'order_status', width: '100px' },
                            { data: 'shipping_address', name: 'shipping_address' },
                            { data: 'notes', name: 'notes' },
                            { data: 'discount_code', name: 'discount_code' },
                            { data: 'discount_amount', name: 'discount_amount' },
                            { data: 'created_at', name: 'created_at', width: '150px' },
                            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '150px' }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25,
                        responsive: true
                    });
                }
            }

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
                tr.innerHTML = '\n                    <td><select class="form-select item-product">'+buildProductOptions(productId)+'</select></td>\n                    <td><input type="number" class="form-control item-qty" min="1" value="'+qty+'"></td>\n                    <td class="item-subtotal">$'+Number(subtotal).toFixed(2)+'</td>\n                    <td><button type="button" class="btn btn-link text-danger p-0 remove-item" title="Remove"><i class="fas fa-times"></i></button></td>\n                ';
                tbody.appendChild(tr);
                updateSaveEnabled();
                updateDiscountAmountDisplay();
            }

            function renderOrderItems(items){
                var tbody = document.getElementById('orderItemsBody');
                tbody.innerHTML = '';
                if (!items || items.length === 0){ addOrderItemRow(); return; }
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

            // Open create modal
            $(document).on('click', '[data-action="create"]', function(){
                $('#orderForm')[0].reset();
                $('#orderMethod').val('POST');
                $('#orderId').val('');
                $('#orderModalLabel').text('Create Order');
                renderOrderItems([]);
                populateDiscounts();
                loadEligibleDiscounts();
                PROVIDER_PREFILL_DISCOUNT = null;
                const modal = new bootstrap.Modal(document.getElementById('orderModal'));
                modal.show();
            });

            // Edit modal - fetch data via show endpoint
            $(document).on('click', '.edit-order', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                var prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                $('#orderForm')[0].reset();
                $('#orderMethod').val('PUT');
                $('#orderId').val(id);
                $('#orderModalLabel').text('Edit Order');
                fetch('/'+prefix+'/orders/'+id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r){ return r.json(); }).then(function(o){
                    if (o.user_id) $('#user_id').val(o.user_id);
                    if (o.order_status) {
                        $('#order_status').val(o.order_status);
                        // Update status dropdown based on role and current status
                        updateStatusDropdown(o.order_status);
                    }
                    if (o.shipping_address) $('#shipping_address').val(o.shipping_address);
                    if (o.notes) $('#notes').val(o.notes);
                    // orderItems may be relation objects
                    var rawItems = (o.order_items || o.orderItems || []);
                    var items = rawItems.map(function(it){ return { product_id: it.product_id, quantity: it.quantity, total: it.total, line_discount: (it.line_discount || 0) }; });
                    if (window.location.pathname.includes('/provider/')){
                        // Keep only this provider's products in the modal
                        items = items.filter(function(it){ return PRODUCTS.some(function(p){ return p.id == it.product_id; }); });
                    }
                    renderOrderItems(items);
                    populateDiscounts();
                    if (window.location.pathname.includes('/admin/')){
                        setTimeout(function(){
                            loadEligibleDiscounts(o.discount_code || '');
                            // computeTotals will be called inside loadEligibleDiscounts after selection
                        }, 0);
                    } else {
                        // Prefill discount amount from server for this provider
                        var providerDiscount = items.reduce(function(s,it){ return s + (parseFloat(it.line_discount)||0); }, 0);
                        PROVIDER_PREFILL_DISCOUNT = providerDiscount;
                        var providerHasDiscount = providerDiscount > 0;
                        $('#discount_code').val(providerHasDiscount ? (o.discount_code || '') : '');
                        // Recompute totals using server discount
                        computeTotals();
                    }
                    const modal = new bootstrap.Modal(document.getElementById('orderModal'));
                    modal.show();
                }).catch(function(){ const modal = new bootstrap.Modal(document.getElementById('orderModal')); modal.show(); });
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
                        if (window.DataTableInstances && window.DataTableInstances['orders-table']){
                            window.DataTableInstances['orders-table'].ajax.reload(null, false);
                        }
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
                        
                        const allowedTransitions = item.allowed_transitions || [];
                        let statusDropdown = '<span class="badge ' + getStatusBadgeClass(item.order_status) + '">' + item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1) + '</span>';
                        
                        if (allowedTransitions.length > 0) {
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
                            '<td>$' + parseFloat(item.unit_price).toFixed(2) + '</td>' +
                            '<td>$' + parseFloat(item.total).toFixed(2) + '</td>' +
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
                        
                        // Reload DataTable
                        if (window.DataTableInstances && window.DataTableInstances['orders-table']) {
                            window.DataTableInstances['orders-table'].ajax.reload(null, false);
                        }
                        
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
                        if (window.DataTableInstances && window.DataTableInstances['orders-table']) {
                            window.DataTableInstances['orders-table'].ajax.reload(null, false);
                        }
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
            
            // Update status dropdown options based on role and current status (for edit modal)
            function updateStatusDropdown(currentStatus) {
                const statusSelect = $('#order_status');
                const isAdmin = window.location.pathname.includes('/admin/');
                const isProvider = window.location.pathname.includes('/provider/');
                
                statusSelect.empty();
                statusSelect.append('<option value="' + currentStatus + '" selected>' + currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1) + '</option>');
                
                if (isAdmin) {
                    // Admin can set any status
                    const allStatuses = ['pending', 'shipped', 'delivered', 'cancelled'];
                    allStatuses.forEach(function(status) {
                        if (status !== currentStatus) {
                            statusSelect.append('<option value="' + status + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</option>');
                        }
                    });
                } else if (isProvider) {
                    // Provider: pending → shipped, shipped → delivered, pending → cancelled
                    if (currentStatus === 'pending') {
                        statusSelect.append('<option value="shipped">Shipped</option>');
                        statusSelect.append('<option value="cancelled">Cancelled</option>');
                    } else if (currentStatus === 'shipped') {
                        statusSelect.append('<option value="delivered">Delivered</option>');
                    }
                }
            }

            // initial bootstrap
            $(document).ready(function(){ initDataTable(); populateDiscounts(); updateSaveEnabled(); computeTotals(); });
        })();
    </script>
    @endpush
</x-app-layout>