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
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    </table>
                    <script>
                    $(function () {
                        $('#users-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: $('#users-table').data('dt-url'),
                            pageLength: $('#users-table').data('dt-page-length'),
                            order: JSON.parse($('#users-table').attr('data-dt-order')),
                            columns: [
                                { data: "id", name: "id", width: "60px" },
                                { data: "name", name: "name" },
                                { data: "email", name: "email" },
                                { data: "created_at", name: "created_at" },
                                { data: "actions", name: "actions", orderable: false, searchable: false }
                            ]
                        });
                    });
                    </script>
                </div>
            </div>
        </div>
    </div>

    @include('admin::partials.user-modal')
</x-app-layout>


