<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Users</h1>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" data-action="create" data-modal="userModal">
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Create User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        @csrf
                        <input type="hidden" id="userId" name="user_id">
                        <input type="hidden" name="_method" id="userMethod" value="POST">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Leave blank when editing to keep current password</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="user">User</option>
                                <option value="provider">Provider</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn" onclick="saveUser()" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="userSaveSpinner" role="status" aria-hidden="true"></span>
                        Save User
                    </button>
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
    @endpush
</x-app-layout>
