<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Orders</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal" data-action="create" data-modal="#orderModal">
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
                    <form id="orderForm">
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
                                
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_id" name="product_id" required>
                                        <option value="">Select Product</option>
                                        @if(auth()->user()->hasRole('provider'))
                                            @foreach(\Modules\Products\Models\Product::where('provider_id', auth()->id())->get() as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->title }} - ${{ $product->price }}</option>
                                            @endforeach
                                        @else
                                            @foreach(\Modules\Products\Models\Product::all() as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->title }} - ${{ $product->price }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
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
                    <button type="button" class="btn btn-primary" onclick="saveOrder()" id="saveOrderBtn" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="orderSaveSpinner" role="status" aria-hidden="true"></span>
                        Save Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Orders DataTable
            if ($('#orders-table').length && !$.fn.DataTable.isDataTable('#orders-table')) {
                const ajaxUrl = window.location.pathname.includes('/admin/') 
                    ? '/admin/orders/data' 
                    : '/provider/orders/data';
                    
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
                        { data: 'created_at', name: 'created_at', width: '150px' },
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
        });
    </script>
    @endpush
</x-app-layout>

