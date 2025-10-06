<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Users</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()">
                    <i class="fas fa-plus"></i> Create User
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="users-table" class="table table-hover" width="100%"
                        data-dt-url="{{ route('admin.users.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    </table>
                    @push('scripts')
                    <script>
                    (function initUsersTableWhenReady(){
                        function start(){
                            var $table = $('#users-table');
                            if (!$table.length || !$.fn || !$.fn.dataTable) { return setTimeout(start, 50); }
                            if ($.fn.dataTable.isDataTable($table)) { return; }
                            window.DataTableInstances = window.DataTableInstances || {};
                            window.DataTableInstances['users-table'] = $table.DataTable({
                                processing: true,
                                serverSide: true,
                                ajax: $table.data('dt-url'),
                                pageLength: $table.data('dt-page-length'),
                                order: JSON.parse($table.attr('data-dt-order')),
                                columns: [
                                    { data: 'id', name: 'id', width: '60px' },
                                    { data: 'name', name: 'name' },
                                    { data: 'email', name: 'email' },
                                    // Custom rendered switch returned by server for verification
                                    { data: 'status', name: 'status', width: '120px', orderable: false, searchable: false },
                                    { data: 'created_at', name: 'created_at' },
                                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                                ]
                            });

                            // Bind verify toggle events (using event delegation)
                            $table.on('change', '.js-verify-toggle', function(){
                                var userId = $(this).data('id');
                                var verify = $(this).is(':checked');
                                // Optimistic UI: keep switch position but revert on error
                                var $switch = $(this);
                                fetch('/admin/users/' + userId + '/verify', {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ verify: verify ? 1 : 0 })
                                }).then(function(r){ return r.json(); })
                                .then(function(resp){
                                    if (!resp.success) { throw new Error(resp.message || 'Failed to update.'); }
                                    // Optional toast/alert
                                    if (window.Swal) Swal.fire('Success', resp.message, 'success');
                                }).catch(function(err){
                                    console.error('Verify toggle failed:', err);
                                    $switch.prop('checked', !verify);
                                    if (window.Swal) Swal.fire('Error', err.message || 'Failed to update.', 'error');
                                });
                            });
                        }
                        if (window.jQuery) { start(); }
                        else { window.addEventListener('load', start); }
                    })();
                    </script>
                    @endpush
                </div>
            </div>
        </div>
    </div>

    @include('admin::partials.user-modal')
</x-app-layout>


