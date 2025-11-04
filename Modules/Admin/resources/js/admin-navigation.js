// Admin Navigation with AJAX and State Management - Enhanced Version
$(document).ready(function() {
    // Global state management
    window.AdminState = {
        currentPage: null,
        tables: {},
        init: function() {
            this.bindNavigation();
            this.bindTableState();
            this.initializeCurrentPage();
        },
        
        bindNavigation: function() {
            // Handle sidebar navigation
            $('.admin-nav-link').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                const page = $(this).data('page');
                
                if (url && page) {
                    AdminState.loadPage(url, page);
                }
            });
        },
        
        loadPage: function(url, page) {
            // Show loading indicator
            this.showLoading();
            
            // Clean up existing tables before loading new content
            this.cleanupTables();
            
            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    // Update URL without page reload
                    history.pushState({page: page}, '', url);
                    
                    // Update content - find the main content area
                    const $mainContent = $('main');
                    if ($mainContent.length) {
                        $mainContent.html(response);
                    } else {
                        // Fallback to body if main not found
                        $('body').html(response);
                    }
                    
                    // Small delay to ensure DOM is ready
                    setTimeout(() => {
                        // Initialize page-specific functionality
                        AdminState.initializePage(page);
                        
                        // Update active navigation
                        AdminState.updateActiveNav(page);
                        
                        AdminState.hideLoading();
                    }, 100);
                },
                error: function(xhr) {
                    console.error('Error loading page:', xhr);
                    AdminState.hideLoading();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load page. Please try again.'
                        });
                    }
                }
            });
        },
        
        cleanupTables: function() {
            // Destroy all existing DataTables
            if (window.usersTable && $.fn.DataTable.isDataTable('#users-table')) {
                try {
                    window.usersTable.destroy();
                } catch (e) {
                    console.log('Error destroying users table:', e);
                }
                window.usersTable = null;
            }
            
            if (window.providersTable && $.fn.DataTable.isDataTable('#providers-table')) {
                try {
                    window.providersTable.destroy();
                } catch (e) {
                    console.log('Error destroying providers table:', e);
                }
                window.providersTable = null;
            }
            
            // Clear any remaining DataTable instances
            $('.dataTable').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    try {
                        $(this).DataTable().destroy();
                    } catch (e) {
                        console.log('Error destroying table:', e);
                    }
                }
            });
        },
        
        initializePage: function(page) {
            console.log('Initializing page:', page);
            
            switch(page) {
                // DataTables are now initialized globally via initializeDataTables() in app.blade.php
                // No page-specific initialization needed
                default:
                    console.log('Page loaded:', page);
                    break;
            }
        },
        
        initializeCurrentPage: function() {
            // Initialize the current page on load
            const currentPath = window.location.pathname;
            let currentPage = null;
            
            if (currentPath.includes('/admin/users')) {
                currentPage = 'users';
            } else if (currentPath.includes('/admin/providers')) {
                currentPage = 'providers';
            } else if (currentPath.includes('/admin/dashboard')) {
                currentPage = 'dashboard';
            }
            
            if (currentPage) {
                console.log('Initializing current page:', currentPage);
                this.initializePage(currentPage);
                this.updateActiveNav(currentPage);
            }
        },
        
        updateActiveNav: function(page) {
            $('.admin-nav-link').removeClass('active');
            $(`.admin-nav-link[data-page="${page}"]`).addClass('active');
        },
        
        bindTableState: function() {
            // Save table state on page unload
            $(window).on('beforeunload', function() {
                AdminState.saveTableStates();
            });
            
            // Restore table state on page load
            $(window).on('load', function() {
                AdminState.restoreTableStates();
            });
        },
        
        saveTableStates: function() {
            const states = {};
            if (window.usersTable && $.fn.DataTable.isDataTable('#users-table')) {
                try {
                    states['users-table'] = window.usersTable.state();
                } catch (e) {
                    console.log('Error saving users table state:', e);
                }
            }
            if (window.providersTable && $.fn.DataTable.isDataTable('#providers-table')) {
                try {
                    states['providers-table'] = window.providersTable.state();
                } catch (e) {
                    console.log('Error saving providers table state:', e);
                }
            }
            localStorage.setItem('adminTableStates', JSON.stringify(states));
        },
        
        restoreTableStates: function() {
            const states = JSON.parse(localStorage.getItem('adminTableStates') || '{}');
            if (window.usersTable && states['users-table']) {
                try {
                    window.usersTable.state(states['users-table']);
                } catch (e) {
                    console.log('Error restoring users table state:', e);
                }
            }
            if (window.providersTable && states['providers-table']) {
                try {
                    window.providersTable.state(states['providers-table']);
                } catch (e) {
                    console.log('Error restoring providers table state:', e);
                }
            }
        },
        
        showLoading: function() {
            if (!$('#admin-loading').length) {
                $('body').append('<div id="admin-loading" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.5); z-index: 9999;"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            }
        },
        
        hideLoading: function() {
            $('#admin-loading').remove();
        }
    };
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page) {
            const url = window.location.pathname;
            AdminState.loadPage(url, event.state.page);
        }
    });
    
    // Initialize admin state management
    AdminState.init();
    
    // DataTables are now initialized globally via initializeDataTables() in app.blade.php
    // No global wrapper functions needed
});