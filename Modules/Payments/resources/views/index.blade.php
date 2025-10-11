<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Payments</h1>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="payments-table" class="table table-hover" width="100%"
                        data-dt-url="{{ route('admin.payments.data') }}"
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
        </script>
    @endpush
</x-app-layout>
