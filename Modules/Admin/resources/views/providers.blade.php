<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Providers</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()">
                    <i class="fas fa-plus"></i> Create Provider
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="providers-table" class="table table-hover" width="100%"
                        data-dt-url="{{ route('admin.providers.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    </table>
                    @push('scripts')
                    <script>
                    (function initProvidersTableWhenReady(){
                        function start(){
                            var $table = $('#providers-table');
                            if (!$table.length || !$.fn || !$.fn.dataTable) { return setTimeout(start, 50); }
                            if ($.fn.dataTable.isDataTable($table)) { return; }
                            window.DataTableInstances = window.DataTableInstances || {};
                            window.DataTableInstances['providers-table'] = $table.DataTable({
                                processing: true,
                                serverSide: true,
                                ajax: $table.data('dt-url'),
                                pageLength: $table.data('dt-page-length'),
                                order: JSON.parse($table.attr('data-dt-order')),
                                columns: [
                                    { data: 'id', name: 'id', width: '60px' },
                                    { data: 'name', name: 'name' },
                                    { data: 'email', name: 'email' },
                                    { data: 'created_at', name: 'created_at' },
                                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                                ]
                            });
                        }
                        if (window.jQuery) { start(); }
                        else { window.addEventListener('load', start); }
                    })();
                    </script>
                    @endpush
                    @push('scripts')
                    <script>
                    // Prevent missing function errors before discount modal is implemented
                    window.openDiscountModal = window.openDiscountModal || function(){
                        Swal && Swal.fire ? Swal.fire('Info','Discount modal not available yet.','info') : alert('Discount modal not available yet.');
                    };
                    </script>
                    @endpush
                </div>
            </div>
        </div>
    </div>

    @include('admin::partials.user-modal')
</x-app-layout>
