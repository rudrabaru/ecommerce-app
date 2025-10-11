// AJAX-powered Providers DataTable with consistent UI
function initProvidersTable() {
    console.log('initProvidersTable called');
    
    // Check if table element exists
    if (!$('#providers-table').length) {
        console.log('Providers table element not found');
        return null;
    }
    
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#providers-table')) {
        console.log('Destroying existing providers table');
        try {
            $('#providers-table').DataTable().destroy();
        } catch (e) {
            console.log('Error destroying existing table:', e);
        }
    }
    
    // Clear the table content
    $('#providers-table').empty();
    
    // Recreate the table structure
    const tableHtml = `
        <thead class="table-light">
            <tr>
                <th data-column="id" data-width="60px">ID</th>
                <th data-column="name">Name</th>
                <th data-column="email">Email</th>
                <th data-column="created_at">Created At</th>
                <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;
    
    $('#providers-table').html(tableHtml);

    // Initialize DataTable with consistent settings
    const providersTable = $('#providers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: $('#providers-table').data('dt-url'),
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error, thrown);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load providers data'
                    });
                }
            }
        },
        pageLength: $('#providers-table').data('dt-page-length') || 25,
        order: JSON.parse($('#providers-table').attr('data-dt-order') || '[[0, "desc"]]'),
        stateSave: true,
        stateDuration: 60 * 60 * 24, // 24 hours
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        drawCallback: function(settings) {
            console.log('Providers DataTable drawCallback executed');
            
            // Re-initialize tooltips after each draw
            $('[data-bs-toggle="tooltip"]').tooltip();
        },
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No providers found',
            zeroRecords: 'No matching providers found'
        },
        responsive: true,
        autoWidth: false,
        initComplete: function() {
            console.log('Providers DataTable initialization complete');
        }
    });

    // Store table reference globally
    window.providersTable = providersTable;
    
    console.log('Providers table initialized successfully:', providersTable);
    return providersTable;
}

// Global event handlers for delete buttons
$(document).off('click', '.js-delete').on('click', '.js-delete', function() {
    const $button = $(this);
    const userId = $button.data('id');
    const deleteUrl = $button.data('delete-url');

    console.log('Delete button clicked:', userId);

    if (window.Swal) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                $button.prop('disabled', true);
                
                $.ajax({
                    url: deleteUrl,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Delete response:', response);
                        
                        if (response.success) {
                            // Reload table
                            if (window.providersTable) {
                                window.providersTable.ajax.reload(null, false);
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'Provider deleted successfully',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to delete provider'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Delete error:', xhr);
                        
                        let errorMessage = 'Failed to delete provider';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            }
        });
    }
});

// Initialize on document ready
$(document).ready(function() {
    console.log('Providers.js document ready');
    
    // Only initialize if we're on the providers page and table exists
    if ($('#providers-table').length && !$.fn.DataTable.isDataTable('#providers-table')) {
        console.log('Auto-initializing providers table');
        initProvidersTable();
    }
});

// Export function for global access
window.initProvidersTable = initProvidersTable;