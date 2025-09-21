<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Orders</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal" onclick="openOrderModal()">
                    <i class="fas fa-plus"></i> Create Order
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="orders-table" class="table table-hover" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
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
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
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
        // DataTable is now initialized globally - no need for individual initialization
        
        function openOrderModal(orderId = null) {
            // Reset form
            $('#orderForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            $('#saveOrderBtn').prop('disabled', true);
            
            if (orderId) {
                // Edit mode
                $('#orderModalLabel').text('Edit Order');
                $('#orderMethod').val('PUT');
                $('#orderId').val(orderId);
                
                // Load order data
                fetch(`/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/orders/${orderId}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    $('#user_id').val(data.user_id);
                    $('#product_id').val(data.product_id);
                    $('#quantity').val(data.quantity);
                    $('#status').val(data.status);
                    $('#shipping_address').val(data.shipping_address);
                    $('#notes').val(data.notes);
                    validateOrderForm();
                })
                .catch(error => {
                    console.error('Error loading order:', error);
                    Swal.fire('Error', 'Error loading order data', 'error');
                });
            } else {
                // Create mode
                $('#orderModalLabel').text('Create Order');
                $('#orderMethod').val('POST');
                $('#orderId').val('');
            }
        }
        
        function saveOrder() {
            const form = document.getElementById('orderForm');
            const formData = new FormData(form);
            const orderId = $('#orderId').val();
            
            let url = '/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/orders';
            if (orderId) {
                url += `/${orderId}`;
            }
            
            // Show loading state
            $('#orderSaveSpinner').removeClass('d-none');
            const saveBtn = document.getElementById('saveOrderBtn');
            saveBtn.disabled = true;
            
            // Clear previous errors
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#orderModal').modal('hide');
                    if (window.DataTableInstances['orders-table']) {
                        window.DataTableInstances['orders-table'].ajax.reload();
                    }
                    Swal.fire('Success', data.message || 'Order saved successfully!', 'success');
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}`).siblings('.invalid-feedback').text(data.errors[field][0]);
                        });
                    }
                    Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
                }
            })
            .catch(error => {
                console.error('Error saving order:', error);
                Swal.fire('Error', 'An error occurred while saving the order.', 'error');
            })
            .finally(() => {
                $('#orderSaveSpinner').addClass('d-none');
                saveBtn.disabled = false;
            });
        }
        
        function deleteOrder(orderId) {
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
                    fetch(`/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/orders/${orderId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (window.DataTableInstances['orders-table']) {
                                window.DataTableInstances['orders-table'].ajax.reload();
                            }
                            Swal.fire('Deleted!', data.message || 'Order deleted successfully!', 'success');
                        } else {
                            Swal.fire('Error', data.message || 'Error deleting order.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting order:', error);
                        Swal.fire('Error', 'An error occurred while deleting the order.', 'error');
                    });
                }
            });
        }
        
        // Form validation for Orders
        function validateOrderForm() {
            const userId = $('#user_id').val();
            const productId = $('#product_id').val();
            const quantity = $('#quantity').val();
            const shippingAddress = $('#shipping_address').val().trim();
            
            let isValid = userId !== '' && productId !== '' && quantity !== '' && shippingAddress !== '';
            
            // Quantity validation
            if (quantity && (isNaN(quantity) || parseInt(quantity) < 1)) {
                isValid = false;
            }
            
            $('#saveOrderBtn').prop('disabled', !isValid);
        }
        
        // Add event listeners for form validation
        $(document).ready(function() {
            $('#user_id, #product_id, #quantity, #shipping_address').on('input change', validateOrderForm);
        });
        
        // Handle DataTable actions
        $(document).on('click', '.edit-order', function() {
            const orderId = $(this).data('id');
            openOrderModal(orderId);
        });
        
        $(document).on('click', '.delete-order', function() {
            const orderId = $(this).data('id');
            deleteOrder(orderId);
        });
    </script>
    @endpush
</x-app-layout>
