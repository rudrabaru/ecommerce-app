// Comprehensive Admin DataTable Test Script
window.AdminComprehensiveTest = {
    testResults: {
        navigation: {},
        datatables: {},
        functionality: {},
        errors: []
    },
    
    // Test all admin sidebar functionalities
    runFullTest: function() {
        console.log('🚀 Starting Comprehensive Admin Test...');
        this.testResults = {
            navigation: {},
            datatables: {},
            functionality: {},
            errors: []
        };
        
        // Test navigation between all admin sections
        this.testNavigation();
        
        // Test DataTable functionality
        this.testDataTables();
        
        // Test specific functionalities
        this.testFunctionalities();
        
        // Generate report
        this.generateReport();
    },
    
    // Test navigation between admin sections
    testNavigation: function() {
        console.log('🧭 Testing Navigation...');
        
        const adminSections = [
            { id: 'dashboard', name: 'Dashboard', url: '/admin/dashboard' },
            { id: 'users', name: 'Users', url: '/admin/users' },
            { id: 'providers', name: 'Providers', url: '/admin/providers' },
            { id: 'products', name: 'Products', url: '/admin/products' },
            { id: 'categories', name: 'Categories', url: '/admin/categories' },
            { id: 'orders', name: 'Orders', url: '/admin/orders' },
            { id: 'payments', name: 'Payments', url: '/admin/payments' },
            { id: 'profile', name: 'Profile', url: '/admin/profile' }
        ];
        
        adminSections.forEach(section => {
            this.testResults.navigation[section.id] = {
                name: section.name,
                url: section.url,
                status: 'pending',
                errors: []
            };
        });
        
        // Test current page
        this.testCurrentPage();
    },
    
    // Test current page functionality
    testCurrentPage: function() {
        const currentPath = window.location.pathname;
        console.log('📍 Current page:', currentPath);
        
        if (currentPath.includes('/admin/users')) {
            this.testUsersPage();
        } else if (currentPath.includes('/admin/providers')) {
            this.testProvidersPage();
        } else if (currentPath.includes('/admin/products')) {
            this.testProductsPage();
        } else if (currentPath.includes('/admin/categories')) {
            this.testCategoriesPage();
        } else if (currentPath.includes('/admin/orders')) {
            this.testOrdersPage();
        } else if (currentPath.includes('/admin/payments')) {
            this.testPaymentsPage();
        } else if (currentPath.includes('/admin/dashboard')) {
            this.testDashboardPage();
        } else if (currentPath.includes('/admin/profile')) {
            this.testProfilePage();
        }
    },
    
    // Test Users page
    testUsersPage: function() {
        console.log('👥 Testing Users Page...');
        
        const tests = {
            tableExists: $('#users-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#users-table'),
            statusToggles: $('.js-verify-toggle').length,
            deleteButtons: $('.js-delete').length,
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.users = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
        
        // Test status toggle functionality
        if (tests.statusToggles > 0) {
            this.testStatusToggle();
        }
        
        // Test delete functionality
        if (tests.deleteButtons > 0) {
            this.testDeleteFunctionality();
        }
    },
    
    // Test Providers page
    testProvidersPage: function() {
        console.log('🏢 Testing Providers Page...');
        
        const tests = {
            tableExists: $('#providers-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#providers-table'),
            deleteButtons: $('.js-delete').length,
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.providers = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Products page
    testProductsPage: function() {
        console.log('📦 Testing Products Page...');
        
        const tests = {
            tableExists: $('#products-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#products-table'),
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.products = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Categories page
    testCategoriesPage: function() {
        console.log('🏷️ Testing Categories Page...');
        
        const tests = {
            tableExists: $('#categories-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#categories-table'),
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.categories = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Orders page
    testOrdersPage: function() {
        console.log('🛒 Testing Orders Page...');
        
        const tests = {
            tableExists: $('#orders-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#orders-table'),
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.orders = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Payments page
    testPaymentsPage: function() {
        console.log('💳 Testing Payments Page...');
        
        const tests = {
            tableExists: $('#payments-table').length > 0,
            tableInitialized: $.fn.DataTable.isDataTable('#payments-table'),
            searchInput: $('.dataTables_filter input').length > 0,
            pagination: $('.dataTables_paginate').length > 0
        };
        
        this.testResults.datatables.payments = {
            ...tests,
            status: Object.values(tests).every(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Dashboard page
    testDashboardPage: function() {
        console.log('📊 Testing Dashboard Page...');
        
        const tests = {
            statsCards: $('.stats-card, .card').length > 0,
            charts: $('.chart, canvas').length > 0,
            recentData: $('.recent-users, .recent-products').length > 0
        };
        
        this.testResults.functionality.dashboard = {
            ...tests,
            status: Object.values(tests).some(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test Profile page
    testProfilePage: function() {
        console.log('👤 Testing Profile Page...');
        
        const tests = {
            profileForm: $('form').length > 0,
            inputFields: $('input, textarea, select').length > 0,
            saveButton: $('button[type="submit"], .btn-primary').length > 0
        };
        
        this.testResults.functionality.profile = {
            ...tests,
            status: Object.values(tests).some(test => test) ? 'pass' : 'fail'
        };
    },
    
    // Test status toggle functionality
    testStatusToggle: function() {
        console.log('🔄 Testing Status Toggle...');
        
        const firstToggle = $('.js-verify-toggle').first();
        if (firstToggle.length > 0) {
            const userId = firstToggle.data('id');
            const originalState = firstToggle.is(':checked');
            
            console.log(`Testing toggle for user ${userId}, original state: ${originalState}`);
            
            // Test toggle change
            firstToggle.trigger('change');
            
            // Check if event handler is bound
            const hasEventHandler = $._data(firstToggle[0], 'events') && $._data(firstToggle[0], 'events').change;
            
            this.testResults.functionality.statusToggle = {
                userId: userId,
                originalState: originalState,
                hasEventHandler: !!hasEventHandler,
                status: hasEventHandler ? 'pass' : 'fail'
            };
        }
    },
    
    // Test delete functionality
    testDeleteFunctionality: function() {
        console.log('🗑️ Testing Delete Functionality...');
        
        const firstDeleteBtn = $('.js-delete').first();
        if (firstDeleteBtn.length > 0) {
            const userId = firstDeleteBtn.data('id');
            const deleteUrl = firstDeleteBtn.data('delete-url');
            
            console.log(`Testing delete for user ${userId}, URL: ${deleteUrl}`);
            
            // Check if event handler is bound
            const hasEventHandler = $._data(firstDeleteBtn[0], 'events') && $._data(firstDeleteBtn[0], 'events').click;
            
            this.testResults.functionality.delete = {
                userId: userId,
                deleteUrl: deleteUrl,
                hasEventHandler: !!hasEventHandler,
                status: hasEventHandler ? 'pass' : 'fail'
            };
        }
    },
    
    // Test DataTable functionality
    testDataTables: function() {
        console.log('📊 Testing DataTables...');
        
        const tables = ['users-table', 'providers-table', 'products-table', 'categories-table', 'orders-table', 'payments-table'];
        
        tables.forEach(tableId => {
            const $table = $(`#${tableId}`);
            if ($table.length > 0) {
                const isDataTable = $.fn.DataTable.isDataTable(`#${tableId}`);
                const hasData = $table.find('tbody tr').length > 0;
                const hasSearch = $('.dataTables_filter input').length > 0;
                const hasPagination = $('.dataTables_paginate').length > 0;
                
                this.testResults.datatables[tableId.replace('-table', '')] = {
                    exists: true,
                    initialized: isDataTable,
                    hasData: hasData,
                    hasSearch: hasSearch,
                    hasPagination: hasPagination,
                    status: isDataTable ? 'pass' : 'fail'
                };
            }
        });
    },
    
    // Test specific functionalities
    testFunctionalities: function() {
        console.log('⚙️ Testing Functionalities...');
        
        // Test AJAX navigation
        this.testAjaxNavigation();
        
        // Test state persistence
        this.testStatePersistence();
        
        // Test error handling
        this.testErrorHandling();
    },
    
    // Test AJAX navigation
    testAjaxNavigation: function() {
        console.log('🔄 Testing AJAX Navigation...');
        
        const navLinks = $('.admin-nav-link');
        const hasNavigation = navLinks.length > 0;
        const hasEventHandlers = navLinks.toArray().some(link => 
            $._data(link, 'events') && $._data(link, 'events').click
        );
        
        this.testResults.functionality.ajaxNavigation = {
            hasLinks: hasNavigation,
            hasEventHandlers: hasEventHandlers,
            status: hasNavigation && hasEventHandlers ? 'pass' : 'fail'
        };
    },
    
    // Test state persistence
    testStatePersistence: function() {
        console.log('💾 Testing State Persistence...');
        
        const hasLocalStorage = typeof Storage !== 'undefined';
        const hasStoredStates = localStorage.getItem('adminTableStates') !== null;
        
        this.testResults.functionality.statePersistence = {
            hasLocalStorage: hasLocalStorage,
            hasStoredStates: hasStoredStates,
            status: hasLocalStorage ? 'pass' : 'fail'
        };
    },
    
    // Test error handling
    testErrorHandling: function() {
        console.log('⚠️ Testing Error Handling...');
        
        const hasSwal = typeof Swal !== 'undefined';
        const hasConsole = typeof console !== 'undefined';
        
        this.testResults.functionality.errorHandling = {
            hasSwal: hasSwal,
            hasConsole: hasConsole,
            status: hasSwal && hasConsole ? 'pass' : 'fail'
        };
    },
    
    // Generate comprehensive report
    generateReport: function() {
        console.log('📋 Generating Test Report...');
        
        const report = {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            results: this.testResults
        };
        
        // Calculate overall status
        const allTests = [];
        Object.values(this.testResults.navigation).forEach(test => allTests.push(test.status));
        Object.values(this.testResults.datatables).forEach(test => allTests.push(test.status));
        Object.values(this.testResults.functionality).forEach(test => allTests.push(test.status));
        
        const passedTests = allTests.filter(status => status === 'pass').length;
        const totalTests = allTests.length;
        const overallStatus = passedTests === totalTests ? 'PASS' : 'FAIL';
        
        report.summary = {
            totalTests: totalTests,
            passedTests: passedTests,
            failedTests: totalTests - passedTests,
            overallStatus: overallStatus,
            successRate: Math.round((passedTests / totalTests) * 100)
        };
        
        console.log('🎯 Test Report Summary:');
        console.log(`Overall Status: ${overallStatus}`);
        console.log(`Tests Passed: ${passedTests}/${totalTests} (${report.summary.successRate}%)`);
        console.log('📊 Detailed Results:', report);
        
        // Store report for external access
        window.adminTestReport = report;
        
        return report;
    },
    
    // Quick test for current page
    quickTest: function() {
        console.log('⚡ Running Quick Test...');
        this.testCurrentPage();
        console.log('📊 Quick Test Results:', this.testResults);
    }
};

// Auto-run comprehensive test in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    $(document).ready(function() {
        setTimeout(() => {
            window.AdminComprehensiveTest.runFullTest();
        }, 3000);
    });
}

// Expose test functions globally
window.testAdmin = window.AdminComprehensiveTest;
