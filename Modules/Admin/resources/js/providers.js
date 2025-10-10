$(document).ready(function() {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#providers-table')) {
        $('#providers-table').DataTable().destroy();
    }
    
    // Initialize DataTable with Bootstrap 5 styling
    const providersTable = $('#providers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: $('#providers-table').data('dt-url'),
        pageLength: $('#providers-table').data('dt-page-length'),
        order: JSON.parse($('#providers-table').attr('data-dt-order')),
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'created_at' },
            { data: 'actions', searchable: false, orderable: false }
        ],
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Edit Provider Modal cleanup on hide
    $('#editProviderModal').on('hidden.bs.modal', function() {
        const modal = $(this);
        const form = modal.find('form');
        const saveBtn = $('#editProviderSaveBtn');
        const spinner = $('#editProviderSpinner');
        
        // Reset form and clear errors
        form[0].reset();
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        
        // Reset button and spinner
        saveBtn.prop('disabled', false);
        spinner.addClass('d-none');
    });

    // Edit Provider Modal show
    $('#editProviderModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const userId = button.data('user-id');
        const modal = $(this);
        
        // Clear previous form data and errors
        modal.find('form')[0].reset();
        modal.find('.is-invalid').removeClass('is-invalid');
        modal.find('.invalid-feedback').empty();
        
        // Enable save button
        $('#editProviderSaveBtn').prop('disabled', false);
        
        // Show loading spinner
        $('#editProviderSpinner').removeClass('d-none');
        
        // Fetch provider data
        $.ajax({
            url: `/admin/users/${userId}`,
            method: 'GET',
            success: function(response) {
                // Fill form with provider data
                $('#edit_providerId').val(response.id);
                $('#edit_provider_name').val(response.name);
                $('#edit_provider_email').val(response.email);
                $('#edit_provider_role').val(response.roles[0].name); // Get first role
                
                // Hide loading spinner
                $('#editProviderSpinner').addClass('d-none');
            },
            error: function(xhr) {
                console.error('Error fetching provider data:', xhr);
                modal.modal('hide');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load provider data. Please try again.'
                });
            }
        });
    });

    // Handle form submission
    $('#editProviderForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const userId = $('#edit_providerId').val();
        const saveBtn = $('#editProviderSaveBtn');
        const spinner = $('#editProviderSpinner');
        
        // Disable save button and show spinner
        saveBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').empty();
        
        // Prepare form data
        const formData = new FormData(form[0]);
        formData.append('_method', 'PUT'); // For Laravel PUT method
        
        $.ajax({
            url: `/admin/users/${userId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#editProviderModal').modal('hide');
                providersTable.ajax.reload(null, false);
                
                // Show success message with auto-close
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Provider updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                // Re-enable save button and hide spinner
                saveBtn.prop('disabled', false);
                spinner.addClass('d-none');
                
                const errors = xhr.responseJSON?.errors || {};
                
                // Clear previous errors
                form.find('.invalid-feedback').remove();
                form.find('.is-invalid').removeClass('is-invalid');
                
                // Display new errors
                Object.keys(errors).forEach(field => {
                    const input = form.find(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                });
                
                // Show error alert
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'An error occurred while updating the provider',
                    confirmButtonText: 'OK'
                });
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
                        text: 'Failed to update provider. Please try again.'
                    });
                }
            }
        });
    });

    // Submit form when clicking the save button
    $('#editProviderSaveBtn').on('click', function() {
        $('#editProviderForm').submit();
    });

    // Handle provider deletion
    $(document).on('click', '.js-delete', function() {
        const userId = $(this).data('id');
        const deleteUrl = $(this).data('delete-url');

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        providersTable.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Provider deleted successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to delete provider'
                        });
                    }
                });
            }
        });
    });
});