// Admin Debug Script - Helps identify DataTable inconsistencies
window.AdminDebug = {
    logTableState: function() {
        console.log('=== DataTable State Debug ===');
        console.log('Current URL:', window.location.href);
        console.log('Users table element exists:', $('#users-table').length > 0);
        console.log('Providers table element exists:', $('#providers-table').length > 0);
        
        if (window.usersTable) {
            console.log('Users DataTable instance exists:', window.usersTable);
            console.log('Users table is DataTable:', $.fn.DataTable.isDataTable('#users-table'));
            if ($.fn.DataTable.isDataTable('#users-table')) {
                console.log('Users table state:', window.usersTable.state());
            }
        } else {
            console.log('Users DataTable instance: null');
        }
        
        if (window.providersTable) {
            console.log('Providers DataTable instance exists:', window.providersTable);
            console.log('Providers table is DataTable:', $.fn.DataTable.isDataTable('#providers-table'));
            if ($.fn.DataTable.isDataTable('#providers-table')) {
                console.log('Providers table state:', window.providersTable.state());
            }
        } else {
            console.log('Providers DataTable instance: null');
        }
        
        console.log('Active navigation:', $('.admin-nav-link.active').attr('data-page'));
        console.log('================================');
    },
    
    compareStates: function() {
        const states = JSON.parse(localStorage.getItem('adminTableStates') || '{}');
        console.log('=== Stored States ===');
        console.log('Stored states:', states);
        console.log('====================');
    },
    
    forceReinitialize: function() {
        console.log('=== Force Reinitialize ===');
        
        // Clean up existing tables
        if (window.AdminState && window.AdminState.cleanupTables) {
            window.AdminState.cleanupTables();
        }
        
        // Reinitialize based on current page
        const currentPath = window.location.pathname;
        if (currentPath.includes('/admin/users')) {
            console.log('Reinitializing users table...');
            if (typeof window.initUsersTable === 'function') {
                window.usersTable = window.initUsersTable();
            }
        } else if (currentPath.includes('/admin/providers')) {
            console.log('Reinitializing providers table...');
            if (typeof window.initProvidersTable === 'function') {
                window.providersTable = window.initProvidersTable();
            }
        }
        
        console.log('========================');
    },
    
    testNavigation: function() {
        console.log('=== Navigation Test ===');
        const navLinks = $('.admin-nav-link');
        console.log('Found navigation links:', navLinks.length);
        
        navLinks.each(function(index) {
            const $link = $(this);
            console.log(`Link ${index + 1}:`, {
                text: $link.text().trim(),
                href: $link.attr('href'),
                page: $link.attr('data-page'),
                active: $link.hasClass('active')
            });
        });
        
        console.log('======================');
    }
};

// Auto-run debug in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    // Run debug after page load
    $(document).ready(function() {
        setTimeout(() => {
            window.AdminDebug.logTableState();
            window.AdminDebug.compareStates();
        }, 2000);
    });
    
    // Run debug after AJAX navigation
    $(document).on('ajaxComplete', function() {
        setTimeout(() => {
            window.AdminDebug.logTableState();
        }, 500);
    });
}

// Expose debug functions globally
window.debugAdmin = window.AdminDebug;
