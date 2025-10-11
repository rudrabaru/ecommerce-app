// Admin DataTable Test Script
// This script can be used to test the DataTable functionality

window.AdminTest = {
    // Test status toggle functionality
    testStatusToggle: function() {
        console.log('Testing status toggle functionality...');
        
        // Check if toggle elements exist
        const toggles = document.querySelectorAll('.js-verify-toggle');
        console.log(`Found ${toggles.length} status toggles`);
        
        if (toggles.length > 0) {
            const firstToggle = toggles[0];
            const userId = firstToggle.getAttribute('data-id');
            console.log(`Testing toggle for user ID: ${userId}`);
            
            // Simulate toggle click
            firstToggle.click();
        } else {
            console.log('No status toggles found');
        }
    },
    
    // Test DataTable initialization
    testDataTableInit: function() {
        console.log('Testing DataTable initialization...');
        
        const usersTable = document.getElementById('users-table');
        const providersTable = document.getElementById('providers-table');
        
        if (usersTable) {
            console.log('Users table found:', usersTable);
            if (window.usersTable) {
                console.log('Users DataTable instance exists:', window.usersTable);
            } else {
                console.log('Users DataTable instance not found');
            }
        }
        
        if (providersTable) {
            console.log('Providers table found:', providersTable);
            if (window.providersTable) {
                console.log('Providers DataTable instance exists:', window.providersTable);
            } else {
                console.log('Providers DataTable instance not found');
            }
        }
    },
    
    // Test navigation functionality
    testNavigation: function() {
        console.log('Testing navigation functionality...');
        
        const navLinks = document.querySelectorAll('.admin-nav-link');
        console.log(`Found ${navLinks.length} navigation links`);
        
        navLinks.forEach((link, index) => {
            const page = link.getAttribute('data-page');
            const href = link.getAttribute('href');
            console.log(`Link ${index + 1}: ${page} -> ${href}`);
        });
    },
    
    // Run all tests
    runAllTests: function() {
        console.log('=== Admin DataTable Tests ===');
        this.testDataTableInit();
        this.testNavigation();
        this.testStatusToggle();
        console.log('=== Tests Complete ===');
    }
};

// Auto-run tests in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            window.AdminTest.runAllTests();
        }, 2000);
    });
}
