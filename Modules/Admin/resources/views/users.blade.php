<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Users</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
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
                            <th data-column="id" data-width="60px">ID</th>
                            <th data-column="name">Name</th>
                            <th data-column="email">Email</th>
                            <th data-column="status" data-orderable="false" data-searchable="false">Status</th>
                            <th data-column="created_at">Created At</th>
                            <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
                        </tr>
                    </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin::partials.create-user-modal')
    @include('admin::partials.edit-user-modal')

    @push('scripts')
        @vite('Modules/Admin/resources/js/users.js')
    @endpush
</x-app-layout>
