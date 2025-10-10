<!-- Create Provider Modal -->
<div class="modal fade" id="createProviderModal" tabindex="-1" aria-labelledby="createProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProviderModalLabel">Create Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createProviderForm" data-ajax-submit="1" data-reload-table="providers-table" action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <input type="hidden" name="role" value="provider">
                    
                    <div class="mb-3">
                        <label for="create_provider_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_provider_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_provider_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="create_provider_email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_provider_password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="create_provider_password" name="password" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm('createProviderForm')" id="createProviderSaveBtn" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="createProviderSpinner" role="status" aria-hidden="true"></span>
                    Create Provider
                </button>
            </div>
        </div>
    </div>
</div>

