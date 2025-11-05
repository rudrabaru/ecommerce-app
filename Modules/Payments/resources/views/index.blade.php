<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Payments</h1>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="payments-table" class="table table-hover" width="100%"
                        data-dt-url="{{ auth()->user()->hasRole('admin') ? route('admin.payments.data') : route('provider.payments.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                    <thead class="table-light">
                        <tr>
                            <th data-column="id" data-width="60px">ID</th>
                            <th data-column="order_number">Order Number</th>
                            <th data-column="payment_method">Payment Method</th>
                            <th data-column="amount">Amount</th>
                            <th data-column="status">Status</th>
                            <th data-column="created_at">Created At</th>
                            <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
                        </tr>
                    </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
        /* Admin DataTables Custom Styles */
        .form-check.form-switch {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
        }
        .form-check-input {
            width: 3rem;
            height: 1.5rem;
            margin: 0;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .form-check-input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .dataTables_wrapper {
            padding: 0;
        }
        .dataTables_length,
        .dataTables_filter {
            margin-bottom: 1rem;
        }
        .dataTables_length select,
        .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .dataTables_length select:focus,
        .dataTables_filter input:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            padding: 0.75rem;
            vertical-align: middle;
        }
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .btn-group .btn {
            margin-right: 0.25rem;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
        }
        .dataTables_processing {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0.375rem;
            padding: 1rem;
            z-index: 1000;
        }
        .dataTables_paginate {
            margin-top: 1rem;
        }
        .paginate_button {
            padding: 0.375rem 0.75rem;
            margin-left: 0.125rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            color: #0d6efd;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .paginate_button:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #0d6efd;
        }
        .paginate_button.current {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .paginate_button.disabled {
            color: #6c757d;
            cursor: not-allowed;
            background-color: transparent;
            border-color: #dee2e6;
        }
        .dataTables_info {
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        .table-responsive {
            border: none;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-body .table-responsive {
            margin: 0;
        }
        </style>
    @endpush

    @push('scripts')
        <script>
        // Ensure function is available on window immediately
        window.openPaymentModal = window.openPaymentModal || function() {};
        
        // DataTable is now initialized globally - no need for custom initialization
        
        // Open payment modal function (reassign to ensure it's available)
        // Returns a promise for async operations
        window.openPaymentModal = function(paymentId = null) {
            const form = document.getElementById('paymentForm');
            const modalTitle = document.getElementById('paymentModalLabel');
            
            // Reset form
            form.reset();
            document.querySelectorAll('.form-control').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            if (paymentId) {
                // Edit mode - return promise
                modalTitle.textContent = 'Edit Payment';
                document.getElementById('paymentMethod').value = 'PUT';
                document.getElementById('paymentId').value = paymentId;
                
                // Hide currency field for edit
                const currencyField = document.getElementById('currency');
                if (currencyField) {
                    currencyField.disabled = true;
                    currencyField.required = false;
                    currencyField.closest('.mb-3').style.display = 'none';
                }
                
                // Load payment data - return promise
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                return fetch(`/${prefix}/payments/${paymentId}/edit`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(p => {
                    document.getElementById('order_id').value = p.order_id || '';
                    document.getElementById('payment_method_id').value = p.payment_method_id || '';
                    document.getElementById('amount').value = p.amount || '';
                    document.getElementById('status').value = p.status || 'pending';
                    return true;
                })
                .catch(error => {
                    console.error('Error loading payment:', error);
                    if (window.Swal) Swal.fire('Error', 'Failed to load payment data', 'error');
                    return false;
                });
            } else {
                // Create mode - return resolved promise
                modalTitle.textContent = 'Create Payment';
                document.getElementById('paymentMethod').value = 'POST';
                document.getElementById('paymentId').value = '';
                
                // Show currency field for create
                const currencyField = document.getElementById('currency');
                if (currencyField) {
                    currencyField.disabled = false;
                    currencyField.closest('.mb-3').style.display = 'block';
                }
                return Promise.resolve(true);
            }
        };

        // Initialize modal behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Note: Modal opening and delete actions are now handled by delegated handlers in crud-modals.js
            // No need for show.bs.modal listener or custom delete handler anymore
        });

        // Re-initialize on AJAX page load
        window.addEventListener('ajaxPageLoaded', function() {
            // Note: Modal opening is now handled by delegated handlers in crud-modals.js
        });

        function savePayment() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            const paymentId = document.getElementById('paymentId').value;
            const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
            
            let url = `/${prefix}/payments`;
            if (paymentId) { 
                url += `/${paymentId}`;
                formData.append('_method', 'PUT');
            }
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable using global function
                    window.reloadDataTable('payments-table');
                    
                    if (window.Swal) {
                        Swal.fire('Success', data.message || 'Payment saved successfully', 'success');
                    } else {
                        alert(data.message || 'Payment saved successfully');
                    }
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const input = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = data.errors[key][0];
                                }
                            }
                        });
                    }
                    
                    if (window.Swal) {
                        Swal.fire('Error', data.message || 'Validation error', 'error');
                    } else {
                        alert(data.message || 'Validation error');
                    }
                }
            })
            .catch(error => {
                console.error('Error saving payment:', error);
                if (window.Swal) {
                    Swal.fire('Error', 'An error occurred while saving', 'error');
                } else {
                    alert('An error occurred while saving');
                }
            });
        }

        function deletePayment(id) {
            const confirmFn = window.Swal ?
                () => Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }) :
                () => Promise.resolve({ isConfirmed: window.confirm('Are you sure you want to delete this payment?') });
            
            confirmFn().then(res => {
                if (!res.isConfirmed) return;
                
                const prefix = window.location.pathname.includes('/admin/') ? 'admin' : 'provider';
                fetch(`/${prefix}/payments/${id}`, {
                    method: 'DELETE',
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest', 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload DataTable using global function
                        window.reloadDataTable('payments-table');
                        
                        if (window.Swal) {
                            Swal.fire('Deleted!', data.message || 'Payment deleted successfully', 'success');
                        } else {
                            alert(data.message || 'Payment deleted successfully');
                        }
                    } else {
                        if (window.Swal) {
                            Swal.fire('Error', data.message || 'Failed to delete payment', 'error');
                        } else {
                            alert(data.message || 'Failed to delete payment');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error deleting payment:', error);
                    if (window.Swal) {
                        Swal.fire('Error', 'Failed to delete payment', 'error');
                    } else {
                        alert('Failed to delete payment');
                    }
                });
            });
        }

        // Expose helpers for inline onclick handlers
        window.savePayment = savePayment;
        window.deletePayment = deletePayment;
        </script>
    @endpush

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Create Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        @csrf
                        <input type="hidden" id="paymentId">
                        <input type="hidden" name="_method" id="paymentMethod" value="POST">
                        <div class="mb-3">
                            <label for="order_id" class="form-label">Order</label>
                            <select id="order_id" name="order_id" class="form-select">
                                @php
                                    $ordersQuery = \App\Models\Order::query();
                                    if(auth()->user()->hasRole('provider')) {
                                        $ordersQuery->whereJsonContains('provider_ids', auth()->id());
                                    }
                                    $orders = $ordersQuery->latest()->limit(100)->get();
                                @endphp
                                @foreach($orders as $o)
                                    <option value="{{ $o->id }}">#{{ $o->order_number }} ({{ $o->status }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method_id" class="form-label">Payment Method</label>
                            <select id="payment_method_id" name="payment_method_id" class="form-select">
                                @foreach(\App\Models\PaymentMethod::get() as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->display_name ?? $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary saveBtn" data-module="payments">Save</button>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
