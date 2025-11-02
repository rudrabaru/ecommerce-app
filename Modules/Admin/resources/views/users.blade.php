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
                            <th data-column="email_status" data-orderable="false" data-searchable="false">Email Status</th>
                            <th data-column="status" data-orderable="false" data-searchable="false">Account Status</th>
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
                        <input type="hidden" id="isEditMode" value="false">
                        
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
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye" id="passwordEyeIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                            <div class="form-text" id="passwordHelpText">Leave blank when editing to keep current password</div>
                        </div>
                        
                        <div class="mb-3" id="roleField" style="display: none;">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role">
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

    <script>
    // User CRUD Functions
    window.openUserModal = function(userId = null) {
        const form = document.getElementById('userForm');
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('userModalLabel');
        const saveBtn = document.getElementById('saveUserBtn');
        const roleField = document.getElementById('roleField');
        const passwordHelpText = document.getElementById('passwordHelpText');
        const isEditMode = document.getElementById('isEditMode');
        
        // Reset form
        form.reset();
        document.querySelectorAll('.form-control, .form-select').forEach(el => {
            el.classList.remove('is-invalid');
        });
        saveBtn.disabled = false;
        
        if (userId) {
            // Edit mode
            isEditMode.value = 'true';
            modalTitle.textContent = 'Edit User';
            document.getElementById('userMethod').value = 'PUT';
            document.getElementById('userId').value = userId;
            document.getElementById('password').removeAttribute('required');
            roleField.style.display = 'block';
            passwordHelpText.textContent = 'Leave blank to keep current password';
            
            // Load user data
            fetch(`/admin/users/${userId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('name').value = data.name;
                document.getElementById('email').value = data.email;
                
                // Set role
                const roleName = data.roles && data.roles.length > 0 ? data.roles[0].name : 'user';
                document.getElementById('role').value = roleName;
                
                // Update password help text with note from server
                if (data.password_note) {
                    document.getElementById('passwordHelpText').textContent = data.password_note;
                }
                
                // Trigger form validation
                setTimeout(() => {
                    document.getElementById('name').dispatchEvent(new Event('input'));
                    document.getElementById('email').dispatchEvent(new Event('input'));
                    document.getElementById('role').dispatchEvent(new Event('change'));
                }, 100);
            })
            .catch(error => {
                console.error('Error loading user:', error);
                if (window.Swal) {
                    Swal.fire('Error', 'Failed to load user data', 'error');
                } else {
                    alert('Failed to load user data');
                }
            });
        } else {
            // Create mode
            isEditMode.value = 'false';
            modalTitle.textContent = 'Create User';
            document.getElementById('userMethod').value = 'POST';
            document.getElementById('userId').value = '';
            document.getElementById('password').setAttribute('required', 'required');
            roleField.style.display = 'none';
            passwordHelpText.textContent = 'Password is required for new users';
        }
    };
    
    // Password visibility toggle function
    window.togglePasswordVisibility = function(inputId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById(inputId + 'EyeIcon');
        
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    };
    
    // Helper functions for field validation
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        field.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = message;
        }
    }
    
    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        field.classList.remove('is-invalid');
    }
    
    window.saveUser = function() {
        const form = document.getElementById('userForm');
        const userId = document.getElementById('userId').value;
        const method = document.getElementById('userMethod').value;
        const saveBtn = document.getElementById('saveUserBtn');
        const spinner = document.getElementById('userSaveSpinner');
        const isEditMode = document.getElementById('isEditMode').value === 'true';
        
        // Real-time validation
        let isValid = true;
        
        // Validate name
        const name = document.getElementById('name').value.trim();
        if (!name) {
            showFieldError('name', 'Name is required');
            isValid = false;
        } else {
            clearFieldError('name');
        }
        
        // Validate email
        const email = document.getElementById('email').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) {
            showFieldError('email', 'Email is required');
            isValid = false;
        } else if (!emailRegex.test(email)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        } else {
            clearFieldError('email');
        }
        
        // Validate password
        const password = document.getElementById('password').value;
        if (!isEditMode && !password) {
            showFieldError('password', 'Password is required');
            isValid = false;
        } else if (password && password.length < 6) {
            showFieldError('password', 'Password must be at least 6 characters');
            isValid = false;
        } else {
            clearFieldError('password');
        }
        
        // Validate role (only in edit mode)
        if (isEditMode) {
            const role = document.getElementById('role').value;
            if (!role) {
                showFieldError('role', 'Role is required');
                isValid = false;
            } else {
                clearFieldError('role');
            }
        }
        
        if (!isValid) {
            return;
        }
        
        // Disable button and show spinner
        saveBtn.disabled = true;
        spinner.classList.remove('d-none');
        
        const formData = new FormData(form);
        const url = userId ? `/admin/users/${userId}` : '/admin/users';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                modal.hide();
                
                // Reload DataTable
                window.reloadDataTable('users-table');
                
                // Show success message
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    alert(data.message);
                }
            } else {
                // Show error
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = data.errors[key][0];
                            }
                        }
                    });
                } else if (data.message) {
                    if (window.Swal) {
                        Swal.fire('Error', data.message, 'error');
                    } else {
                        alert(data.message);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error saving user:', error);
            if (window.Swal) {
                Swal.fire('Error', 'Failed to save user', 'error');
            } else {
                alert('Failed to save user');
            }
        })
        .finally(() => {
            saveBtn.disabled = false;
            spinner.classList.add('d-none');
        });
    };
    
    window.deleteUser = function(userId) {
        const confirmFn = window.Swal ?
            () => Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }) :
            () => Promise.resolve({ isConfirmed: window.confirm('Are you sure you want to delete this user?') });
        
        confirmFn().then(result => {
            if (!result.isConfirmed) return;
            
            fetch(`/admin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload DataTable
                    window.reloadDataTable('users-table');
                    
                    if (window.Swal) {
                        Swal.fire('Deleted!', data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire('Error', data.message, 'error');
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                if (window.Swal) {
                    Swal.fire('Error', 'Failed to delete user', 'error');
                } else {
                    alert('Failed to delete user');
                }
            });
        });
    };
    
    // Initialize modal behavior
    document.addEventListener('DOMContentLoaded', function() {
        const userModal = document.getElementById('userModal');
        if (userModal) {
            userModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (button && button.dataset.action === 'create') {
                    openUserModal(null);
                }
            });
        }
    });
    
    // Re-initialize on AJAX page load
    window.addEventListener('ajaxPageLoaded', function() {
        const userModal = document.getElementById('userModal');
        if (userModal) {
            userModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (button && button.dataset.action === 'create') {
                    openUserModal(null);
                }
            });
        }
    });
    </script>
</x-app-layout>

