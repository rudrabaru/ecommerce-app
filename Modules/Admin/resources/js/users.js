// Initialize DataTable
$(document).ready(function() {
    // Modal cleanup on hide
    $('#editUserModal').on('hidden.bs.modal', function() {
        const modal = $(this);
        const form = modal.find('form');
        const saveBtn = $('#editUserSaveBtn');
        const spinner = $('#editUserSpinner');
        
        // Reset form and clear errors
        form[0].reset();
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        
        // Reset button and spinner
        saveBtn.prop('disabled', false);
        spinner.addClass('d-none');
    });
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#users-table')) {
        $('#users-table').DataTable().destroy();
    }
    
    const usersTable = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: $('#users-table').data('dt-url'),
        pageLength: $('#users-table').data('dt-page-length'),
        order: JSON.parse($('#users-table').attr('data-dt-order')),
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'actions', searchable: false, orderable: false }
        ],
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Edit User Modal
    $('#editUserModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const userId = button.data('user-id');
        const modal = $(this);
        
        // Clear previous form data and errors
        modal.find('form')[0].reset();
        modal.find('.is-invalid').removeClass('is-invalid');
        modal.find('.invalid-feedback').empty();
        
        // Enable save button
        $('#editUserSaveBtn').prop('disabled', false);
        
        // Show loading spinner
        $('#editUserSpinner').removeClass('d-none');
        
        // Fetch user data
        $.ajax({
            url: `/admin/users/${userId}`,
            method: 'GET',
            success: function(response) {
                // Fill form with user data
                $('#edit_userId').val(response.id);
                $('#edit_name').val(response.name);
                $('#edit_email').val(response.email);
                $('#edit_role').val(response.roles[0]?.name || '');
                
                // Hide loading spinner
                $('#editUserSpinner').addClass('d-none');
            },
            error: function(xhr) {
                console.error('Error fetching user data:', xhr);
                modal.modal('hide');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load user data. Please try again.'
                });
            }
        });
    });

    // Handle form submission
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const userId = $('#edit_userId').val();
        const saveBtn = $('#editUserSaveBtn');
        const spinner = $('#editUserSpinner');
        
        // Disable save button and show spinner
        saveBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').empty();
        
        $.ajax({
            url: `/admin/users/${userId}`,
            method: 'PUT',
            data: form.serialize(),
            success: function(response) {
                $('#editUserModal').modal('hide');
                usersTable.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'User updated successfully'
                });
            },
            error: function(xhr) {
                // Re-enable save button and hide spinner
                saveBtn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (xhr.status === 422) {
                    // Handle validation errors
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(field => {
                        const input = form.find(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update user. Please try again.'
                    });
                }
            }
        });
    });

    // Submit form when clicking the save button
    $('#editUserSaveBtn').on('click', function() {
        $('#editUserForm').submit();
    });
});