<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createUserForm" data-ajax-submit="1" data-reload-table="users-table" action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="create_email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="create_password" name="password" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <input type="hidden" name="role" value="user">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createUserSaveBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="createUserSpinner" role="status" aria-hidden="true"></span>
                    Create User
                </button>
            </div>
        </div>
    </div>
</div>

