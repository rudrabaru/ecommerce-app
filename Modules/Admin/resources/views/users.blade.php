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
                            <th>Role</th>
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
                                { data: "role", name: "role", orderable: false, searchable: false },
                                { data: "actions", name: "actions", orderable: false, searchable: false }
                            ]
                        });
                    });
                    </script>
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

    <script>
        // DataTable is now initialized globally - no need for individual initialization
        
        function openUserModal(userId = null) {
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
                    $('#role').val(data.user.roles[0]?.name || '');
                    // Trigger validation after prefilling
                    setTimeout(() => {
                        validateForm();
                        // Trigger input events to update validation state
                        $('#name, #email, #role').trigger('input');
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading user:', error);
                    Swal.fire('Error', 'Error loading user data', 'error');
                });
            } else {
                // Create mode
                $('#userModalLabel').text('Create User');
                $('#userMethod').val('POST');
                $('#userId').val('');
                $('#password').prop('required', true);
                $('#password').next('.form-text').hide();
            }
        }
        
        function saveUser() {
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
                    if (window.DataTableInstances['users-table']) {
                        window.DataTableInstances['users-table'].ajax.reload();
                    }
                    Swal.fire('Success', data.message || 'User saved successfully!', 'success');
                } else {
                    // Handle validation errors
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
        
        function deleteUser(userId) {
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
                            if (window.DataTableInstances['users-table']) {
                                window.DataTableInstances['users-table'].ajax.reload();
                            }
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
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                isValid = false;
            }
            
            // Password validation (only for create or if password is provided)
            if ((!isEdit || password !== '') && password.length < 8) {
                isValid = false;
            }
            
            $('#saveUserBtn').prop('disabled', !isValid);
        }
        
        // Add event listeners for form validation
        $(document).ready(function() {
            $('#name, #email, #password, #role').on('input change', validateForm);
        });
        
        // Handle DataTable actions
        $(document).on('click', '.edit-user', function() {
            const userId = $(this).data('id');
            openUserModal(userId);
        });
        
        $(document).on('click', '.delete-user', function() {
            const userId = $(this).data('id');
            deleteUser(userId);
        });
    </script>
</x-app-layout>


