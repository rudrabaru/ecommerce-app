<!-- Shared modal and CRUD JavaScript for Users/Providers management -->

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
                    
                    <div class="mb-3" id="roleGroup">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="provider">Provider</option>
                            <option value="user">User</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()" id="saveUserBtn" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="userSaveSpinner" role="status" aria-hidden="true"></span>
                    Save User
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function reloadCurrentTable() {
        try {
            if (window.DataTableInstances) {
                if (window.DataTableInstances['providers-table']) {
                    window.DataTableInstances['providers-table'].ajax.reload();
                }
                if (window.DataTableInstances['users-table']) {
                    window.DataTableInstances['users-table'].ajax.reload();
                }
            } else if ($ && $.fn && $.fn.dataTable) {
                if ($.fn.dataTable.isDataTable('#providers-table')) {
                    $('#providers-table').DataTable().ajax.reload();
                }
                if ($.fn.dataTable.isDataTable('#users-table')) {
                    $('#users-table').DataTable().ajax.reload();
                }
            }
        } catch (e) {
            console.warn('Failed to reload datatable', e);
        }
    }

    function isProvidersPage() {
        return window.location && window.location.pathname && window.location.pathname.indexOf('/admin/providers') === 0;
    }
    function defaultRoleForPage() {
        return isProvidersPage() ? 'provider' : 'user';
    }

    window.openUserModal = function(userId = null) {
        // Reset form
        $('#userForm')[0].reset();
        $('.form-control').removeClass('is-invalid');
        $('#saveUserBtn').prop('disabled', true);
        
        if (userId) {
            // Edit mode
            $('#userModalLabel').text('Edit User');
            $('#userMethod').val('PUT');
            $('#userId').val(userId);
            $('#password').prop('required', false);
            $('#password').next('.form-text').show();
            $('#roleGroup').show();
            
            // Load user data
            fetch(`/admin/users/${userId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                $('#name').val(data.user.name);
                $('#email').val(data.user.email);
                const currentRole = data.user.roles[0]?.name || '';
                $('#role').val(currentRole);
                // Update title contextually
                if (currentRole === 'provider') {
                    $('#userModalLabel').text('Edit Provider');
                } else {
                    $('#userModalLabel').text('Edit User');
                }
                // Trigger validation after prefilling
                setTimeout(() => {
                    validateForm();
                    $('#name, #email, #role').trigger('input');
                }, 100);
            })
            .catch(error => {
                console.error('Error loading user:', error);
                Swal.fire('Error', 'Error loading user data', 'error');
            });
        } else {
            // Create mode
            const defRole = defaultRoleForPage();
            $('#role').val(defRole);
            $('#roleGroup').hide(); // hide role selector for create
            $('#userModalLabel').text(defRole === 'provider' ? 'Create Provider' : 'Create User');
            $('#userMethod').val('POST');
            $('#userId').val('');
            $('#password').prop('required', true);
            $('#password').next('.form-text').hide();
        }
    }
    
    window.saveUser = function() {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);
        const userId = $('#userId').val();
        
        let url = '/admin/users';
        if (userId) {
            url += `/${userId}`;
        }
        
        // Show loading state
        $('#userSaveSpinner').removeClass('d-none');
        const saveBtn = document.getElementById('saveUserBtn');
        saveBtn.disabled = true;
        
        // Clear previous errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#userModal').modal('hide');
                reloadCurrentTable();
                Swal.fire('Success', data.message || 'Saved successfully!', 'success');
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(data.errors[field][0]);
                    });
                }
                Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving user:', error);
            Swal.fire('Error', 'An error occurred while saving the user.', 'error');
        })
        .finally(() => {
            $('#userSaveSpinner').addClass('d-none');
            saveBtn.disabled = false;
        });
    }
    
    window.deleteUser = function(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/users/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reloadCurrentTable();
                        Swal.fire('Deleted!', data.message || 'User deleted successfully!', 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Error deleting user.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting user:', error);
                    Swal.fire('Error', 'An error occurred while deleting the user.', 'error');
                });
            }
        });
    }
    
    // Form validation
    function validateForm() {
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const role = $('#role').val();
        const isEdit = $('#userId').val() !== '';
        
        let isValid = name !== '' && email !== '' && role !== '';
        
        if (!isEdit) {
            isValid = isValid && password !== '';
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            isValid = false;
        }
        
        if ((!isEdit || password !== '') && password.length < 8) {
            isValid = false;
        }
        
        $('#saveUserBtn').prop('disabled', !isValid);
    }
    
    (function bindValidationWhenReady(){
        function start(){
            if (!window.jQuery) { return setTimeout(start, 50); }
            $('#name, #email, #password, #role').off('input change').on('input change', validateForm);
        }
        start();
    })();
    
    $(document).on('click', '.edit-user', function() {
        const userId = $(this).data('id');
        openUserModal(userId);
    });
    
    $(document).on('click', '.delete-user', function() {
        const userId = $(this).data('id');
        deleteUser(userId);
    });
</script>
@endpush
