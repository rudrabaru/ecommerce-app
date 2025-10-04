<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Products</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openProductModal()">
                    <i class="fas fa-plus"></i> Create Product
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="products-table" class="table table-hover" width="100%"
                        data-dt-url="{{ route('admin.products.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Provider</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
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

    @include('admin.products.modal')

    @push('scripts')
    <script>
        (function initProductsTableWhenReady(){
            function start(){
                var $table = $('#products-table');
                if (!$table.length || !$.fn || !$.fn.dataTable) { return setTimeout(start, 50); }
                if ($.fn.dataTable.isDataTable($table)) { return; }
                window.DataTableInstances = window.DataTableInstances || {};
                window.DataTableInstances['products-table'] = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: $table.data('dt-url'),
                    pageLength: $table.data('dt-page-length'),
                    order: JSON.parse($table.attr('data-dt-order')),
                    columns: [
                        { data: 'id', name: 'id', width: '60px' },
                        { data: 'title', name: 'title' },
                        { data: 'provider_name', name: 'provider_name' },
                        { data: 'category_name', name: 'category_name' },
                        { data: 'price', name: 'price' },
                        { data: 'stock', name: 'stock' },
                        { data: 'status', name: 'status' },
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
</x-app-layout>

