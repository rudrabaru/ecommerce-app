// AJAX-powered Users DataTable with consistent UI and real-time status toggle
function initUsersTable() {
    console.log('initUsersTable called');
    
    // Check if table element exists
    if (!$('#users-table').length) {
        console.log('Users table element not found');
        return null;
    }
    
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#users-table')) {
        console.log('Destroying existing users table');
        try {
            $('#users-table').DataTable().destroy();
        } catch (e) {
            console.log('Error destroying existing table:', e);
        }
    }
    
    // Clear the table content
    $('#users-table').empty();
    
    // Recreate the table structure
    const tableHtml = `
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
        <tbody></tbody>
    `;
    
    $('#users-table').html(tableHtml);

    // Initialize DataTable with consistent settings
    const usersTable = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: $('#users-table').data('dt-url'),
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error, thrown);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load users data'
                    });
                }
            }
        },
        pageLength: $('#users-table').data('dt-page-length') || 25,
        order: JSON.parse($('#users-table').attr('data-dt-order') || '[[0, "desc"]]'),
        stateSave: true,
        stateDuration: 60 * 60 * 24, // 24 hours
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        drawCallback: function(settings) {
            console.log('DataTable drawCallback executed');
            
            // Re-initialize tooltips after each draw
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Ensure consistent styling for status toggles
            $('.js-verify-toggle').each(function() {
                const $toggle = $(this);
                const isChecked = $toggle.is(':checked');
                
                // Apply consistent styling
                $toggle.closest('.form-check').attr('title', isChecked ? 'Verified' : 'Unverified');
                
                // Ensure proper Bootstrap styling
                if (isChecked) {
                    $toggle.closest('.form-check-input').addClass('checked');
                } else {
                    $toggle.closest('.form-check-input').removeClass('checked');
                }
            });
        },
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No users found',
            zeroRecords: 'No matching users found'
        },
        responsive: true,
        autoWidth: false,
        initComplete: function() {
            console.log('Users DataTable initialization complete');
        }
    });

    // Store table reference globally
    window.usersTable = usersTable;
    
    console.log('Users table initialized successfully:', usersTable);
    return usersTable;
}

// Global event handlers for status toggle (using event delegation)
$(document).off('change', '.js-verify-toggle').on('change', '.js-verify-toggle', function() {
    const $toggle = $(this);
    const userId = $toggle.data('id');
    const isVerified = $toggle.is(':checked');
    const originalState = !isVerified; // Store original state for rollback
    
    console.log('Status toggle changed:', userId, isVerified);
    
    // Show loading state
    $toggle.prop('disabled', true);
    
    // Make AJAX request
    $.ajax({
        url: `/admin/users/${userId}/verify`,
        method: 'POST',
        data: {
            verify: isVerified ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Status toggle response:', response);
            
            if (response.success) {
                // Update tooltip
                $toggle.closest('.form-check').attr('title', isVerified ? 'Verified' : 'Unverified');
                
                // Show success message
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: isVerified ? 'User Verified' : 'User Unverified',
                        text: response.message || (isVerified ? 'User has been verified successfully' : 'User verification has been removed'),
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
                
                // Reload table to ensure consistency
                if (window.usersTable) {
                    window.usersTable.ajax.reload(null, false);
                }
            } else {
                // Revert toggle on failure
                $toggle.prop('checked', originalState);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update verification status'
                    });
                }
            }
        },
        error: function(xhr) {
            console.error('Status toggle error:', xhr);
            
            // Revert toggle on error
            $toggle.prop('checked', originalState);
            
            let errorMessage = 'Failed to update verification status';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        },
        complete: function() {
            // Re-enable toggle
            $toggle.prop('disabled', false);
        }
    });
});

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
                            if (window.usersTable) {
                                window.usersTable.ajax.reload(null, false);
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'User deleted successfully',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to delete user'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Delete error:', xhr);
                        
                        let errorMessage = 'Failed to delete user';
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

// Auto-refresh mechanism for admin panel
let autoRefreshInterval = null;

function startAutoRefresh() {
    // Clear existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    // Refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (window.usersTable) {
            console.log('Auto-refreshing users table...');
            window.usersTable.ajax.reload(null, false);
        }
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Initialize on document ready
$(document).ready(function() {
    console.log('Users.js document ready');
    
    // Only initialize if we're on the users page and table exists
    if ($('#users-table').length && !$.fn.DataTable.isDataTable('#users-table')) {
        console.log('Auto-initializing users table');
        initUsersTable();
        
        // Start auto-refresh after table is initialized
        setTimeout(function() {
            startAutoRefresh();
        }, 1000);
    }
});

// Stop auto-refresh when page is hidden (browser tab not active)
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Stop auto-refresh when page is unloaded
$(window).on('beforeunload', function() {
    stopAutoRefresh();
});

// Manual refresh button functionality
$(document).ready(function() {
    // Manual refresh button
    $('#refresh-users-btn').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        // Show loading state
        $btn.prop('disabled', true);
        $icon.removeClass('fa-sync-alt').addClass('fa-spinner fa-spin');
        
        if (window.usersTable) {
            window.usersTable.ajax.reload(null, false);
            
            // Reset button state after a short delay
            setTimeout(function() {
                $btn.prop('disabled', false);
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-sync-alt');
            }, 1000);
        } else {
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spinner fa-spin').addClass('fa-sync-alt');
        }
    });
    
    // Update auto-refresh indicator
    function updateAutoRefreshIndicator(isActive) {
        const $indicator = $('#auto-refresh-indicator');
        if (isActive) {
            $indicator.removeClass('bg-secondary').addClass('bg-success');
            $indicator.attr('title', 'Auto-refresh enabled (every 30 seconds)');
        } else {
            $indicator.removeClass('bg-success').addClass('bg-secondary');
            $indicator.attr('title', 'Auto-refresh disabled');
        }
    }
    
    // Override the auto-refresh functions to update indicator
    const originalStartAutoRefresh = startAutoRefresh;
    const originalStopAutoRefresh = stopAutoRefresh;
    
    window.startAutoRefresh = function() {
        originalStartAutoRefresh();
        updateAutoRefreshIndicator(true);
    };
    
    window.stopAutoRefresh = function() {
        originalStopAutoRefresh();
        updateAutoRefreshIndicator(false);
    };
});

// Export function for global access
window.initUsersTable = initUsersTable;