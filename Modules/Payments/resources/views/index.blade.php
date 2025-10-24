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
                            <th data-column="order_id">Order ID</th>
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
        // AJAX-powered Payments DataTable
        function initPaymentsTable() {
            console.log('initPaymentsTable called');
            
            if (!$('#payments-table').length) {
                console.log('Payments table element not found');
                return null;
            }
            
            if ($.fn.DataTable.isDataTable('#payments-table')) {
                console.log('Destroying existing payments table');
                try {
                    $('#payments-table').DataTable().destroy();
                } catch (e) {
                    console.log('Error destroying existing table:', e);
                }
            }
            
            $('#payments-table').empty();
            
            const tableHtml = `
                <thead class="table-light">
                    <tr>
                        <th data-column="id" data-width="60px">ID</th>
                        <th data-column="order_id">Order ID</th>
                        <th data-column="amount">Amount</th>
                        <th data-column="status">Status</th>
                        <th data-column="created_at">Created At</th>
                        <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
            
            $('#payments-table').html(tableHtml);

            const paymentsTable = $('#payments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: $('#payments-table').data('dt-url'),
                    type: 'GET',
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX error:', error, thrown);
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load payments data'
                            });
                        }
                    }
                },
                pageLength: $('#payments-table').data('dt-page-length') || 25,
                order: JSON.parse($('#payments-table').attr('data-dt-order') || '[[0, "desc"]]'),
                stateSave: true,
                stateDuration: 60 * 60 * 24,
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'order_id', name: 'order_id' },
                    { data: 'amount', name: 'amount' },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                drawCallback: function(settings) {
                    console.log('Payments DataTable drawCallback executed');
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No payments found',
                    zeroRecords: 'No matching payments found'
                },
                responsive: true,
                autoWidth: false,
                initComplete: function() {
                    console.log('Payments DataTable initialization complete');
                }
            });

            window.paymentsTable = paymentsTable;
            console.log('Payments table initialized successfully:', paymentsTable);
            return paymentsTable;
        }

        $(document).ready(function() {
            console.log('Payments.js document ready');
            
            if ($('#payments-table').length && !$.fn.DataTable.isDataTable('#payments-table')) {
                console.log('Auto-initializing payments table');
                initPaymentsTable();
            }
        });

        window.initPaymentsTable = initPaymentsTable;

        // CRUD helpers for payments
        function openPaymentModal(paymentId = null) {
            $('#paymentForm')[0].reset();
            $('#paymentId').val('');
            $('#paymentMethod').val('POST');
            if (paymentId) {
                $('#paymentModalLabel').text('Edit Payment');
                $('#paymentMethod').val('PUT');
                $('#paymentId').val(paymentId);
                fetch(`/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/payments/${paymentId}/edit`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r=>r.json())
                .then(p=>{
                    $('#order_id').val(p.order_id);
                    $('#payment_method_id').val(p.payment_method_id);
                    $('#amount').val(p.amount);
                    $('#currency').val(p.currency || 'USD');
                    $('#status').val(p.status);
                });
            } else {
                $('#paymentModalLabel').text('Create Payment');
            }
        }

        function savePayment() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            const paymentId = $('#paymentId').val();
            let url = "/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/payments";
            if (paymentId) { url += `/${paymentId}`; }
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            })
            .then(r=>r.json()).then(data=>{
                if (data.success) {
                    $('#paymentModal').modal('hide');
                    $('#payments-table').DataTable().ajax.reload();
                    Swal.fire('Success', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message || 'Validation error', 'error');
                }
            }).catch(()=> Swal.fire('Error','An error occurred','error'));
        }

        function deletePayment(id) {
            Swal.fire({ title:'Are you sure?', icon:'warning', showCancelButton:true }).then(res=>{
                if (!res.isConfirmed) return;
                fetch(`/{{ auth()->user()->hasRole('admin') ? 'admin' : 'provider' }}/payments/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).then(r=>r.json()).then(data=>{
                    if (data.success) {
                        $('#payments-table').DataTable().ajax.reload();
                        Swal.fire('Deleted', data.message, 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete', 'error');
                    }
                });
            });
        }
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
                                    if(auth()->user()->hasRole('provider')) {$ordersQuery->where('provider_id', auth()->id());}
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <input type="text" class="form-control" id="currency" name="currency" value="USD" required>
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
                    <button type="button" class="btn btn-primary" onclick="savePayment()">Save</button>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
