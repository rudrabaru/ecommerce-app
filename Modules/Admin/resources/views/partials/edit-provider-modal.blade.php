<!-- Edit Provider Modal -->
<div class="modal fade" id="editProviderModal" tabindex="-1" aria-labelledby="editProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProviderModalLabel">Edit Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProviderForm" data-ajax-submit="1" data-reload-table="providers-table" method="POST">
                    @csrf
                    <input type="hidden" id="edit_providerId" name="user_id">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="mb-3">
                        <label for="edit_provider_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_provider_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_provider_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit_provider_email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_provider_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="edit_provider_password" name="password">
                        <div class="invalid-feedback"></div>
                        <div class="form-text">Leave blank to keep current password</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_provider_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_provider_role" name="role" required>
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
                <button type="button" class="btn btn-primary" id="editProviderSaveBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="editProviderSpinner" role="status" aria-hidden="true"></span>
                    Update Provider
                </button>
            </div>
        </div>
    </div>
</div>

