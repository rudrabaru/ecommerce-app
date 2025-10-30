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
                                    <div class="form-text">Select a discount to apply. Discount applicability will be calculated per product.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount_amount_display" class="form-label">Discount Amount</label>
                                    <div id="discount_amount_display" class="fw-bold">-</div>
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

    @push('scripts')
    <script>
        (function(){
            // Preload products & discounts as JSON for modal population (fetched server-side at render time)
            @php
                $__orders_modal_products = auth()->user()->hasRole('provider') ? \Modules\Products\Models\Product::where('provider_id', auth()->id())->get() : \Modules\Products\Models\Product::all();
                $__orders_modal_discounts = \App\Models\DiscountCode::active()->validNow()->notExceededUsage()->get();
            @endphp
            var PRODUCTS = {!! $__orders_modal_products->toJson() !!};
            var DISCOUNTS = {!! $__orders_modal_discounts->toJson() !!};

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
                var subtotal = item.total || 0;
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

            function updateDiscountAmountDisplay(){
                var items = gatherItems();
                var selectedCode = $('#discount_code').val();
                if (!selectedCode) { $('#discount_amount_display').text('-'); return; }
                var discount = DISCOUNTS.find(function(d){ return d.code == selectedCode; });
                if (!discount) { $('#discount_amount_display').text('-'); return; }
                // Compute approximate discount client-side using PRODUCTS data
                var applicable = items.map(function(it){ var p = PRODUCTS.find(function(x){ return x.id == it.product_id; }); if (!p) return null; return { line: (parseFloat(p.price)||0)*it.quantity, category_id: p.category_id, provider_id: p.provider_id }; }).filter(Boolean);
                var applicableTotal = applicable.filter(function(a){ return discount.categories && discount.categories.length ? discount.categories.includes(a.category_id) : true; }).reduce(function(s,a){ return s + a.line; }, 0);
                if (applicableTotal <= 0) { $('#discount_amount_display').text('-'); return; }
                var amount = 0;
                if (discount.discount_type === 'percent') {
                    amount = applicableTotal * (discount.discount_value/100);
                } else {
                    amount = discount.discount_value;
                }
                $('#discount_amount_display').text('$'+(amount?amount.toFixed(2):'-'));
            }

            // Bind UI events
            $(document).on('click', '#addItemBtn', function(){ addOrderItemRow(); });
            $(document).on('click', '.remove-item', function(){ $(this).closest('tr').remove(); updateSaveEnabled(); updateDiscountAmountDisplay(); });
            $(document).on('change', '.item-product, .item-qty, #discount_code', function(){
                var tr = $(this).closest('tr');
                var price = parseFloat(tr.find('.item-product option:selected').data('price') || 0);
                var qty = parseInt(tr.find('.item-qty').val() || 1);
                tr.find('.item-subtotal').text('$'+(price*qty).toFixed(2));
                updateSaveEnabled();
                updateDiscountAmountDisplay();
            });

            function updateSaveEnabled(){
                var rows = $('#orderItemsBody tr');
                var ok = rows.length > 0;
                rows.each(function(){
                    var pid = $(this).find('.item-product').val();
                    var qty = $(this).find('.item-qty').val();
                    if (!pid || !qty){ ok = false; return false; }
                });
                $('#saveOrderBtn').prop('disabled', !ok || !$('#user_id').val());
            }

            // Populate discount select on modal open
            function populateDiscounts(){
                var sel = $('#discount_code');
                sel.html('<option value="">No discount</option>');
                DISCOUNTS.forEach(function(d){ sel.append('<option value="'+d.code+'">'+d.code+' - '+(d.discount_type==='percent'?d.discount_value+'%':'$'+d.discount_value) +'</option>'); });
            }

            // Open create modal
            $(document).on('click', '[data-action="create"]', function(){
                $('#orderForm')[0].reset();
                $('#orderMethod').val('POST');
                $('#orderId').val('');
                $('#orderModalLabel').text('Create Order');
                renderOrderItems([]);
                populateDiscounts();
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
                    if (o.order_status) $('#order_status').val(o.order_status);
                    if (o.shipping_address) $('#shipping_address').val(o.shipping_address);
                    if (o.notes) $('#notes').val(o.notes);
                    // orderItems may be relation objects
                    var items = (o.order_items || o.orderItems || []).map(function(it){ return { product_id: it.product_id, quantity: it.quantity, total: it.total }; });
                    renderOrderItems(items);
                    populateDiscounts();
                    if (o.discount_code) $('#discount_code').val(o.discount_code);
                    updateDiscountAmountDisplay();
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
            $('#orderForm').on('submit', function(e){ e.preventDefault(); window.saveOrder(); });

            // initial bootstrap
            $(document).ready(function(){ initDataTable(); populateDiscounts(); updateSaveEnabled(); });
        })();
    </script>
    @endpush
</x-app-layout>