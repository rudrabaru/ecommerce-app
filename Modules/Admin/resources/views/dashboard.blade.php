<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Admin Dashboard</h1>
            <div class="text-muted">
                <i class="fas fa-calendar-alt me-2"></i>
                {{ now()->format('F j, Y') }}
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-users">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Providers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-providers">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-store fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Categories</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-categories">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tags fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Total Products</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-products">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-primary js-ajax-link">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-users">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Products</h6>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-primary js-ajax-link">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Provider</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-products">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dashboard initialization function
        function initializeAdminDashboard() {
            loadDashboardData();
        }
        
        // Auto-initialize on page load or AJAX navigation
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeAdminDashboard);
        } else {
            initializeAdminDashboard();
        }
        
        // Also initialize when AJAX page is loaded
        window.addEventListener('ajaxPageLoaded', function() {
            setTimeout(initializeAdminDashboard, 100);
        });

        function loadDashboardData() {
            // Load stats
            fetch('/admin/dashboard/stats', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                $('#total-users').text(data.total_users || 0);
                $('#total-providers').text(data.total_providers || 0);
                $('#total-categories').text(data.total_categories || 0);
                $('#total-products').text(data.total_products || 0);
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });

            // Load recent users
            fetch('/admin/dashboard/recent-users', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="4" class="text-center text-muted">No recent users</td></tr>';
                } else {
                    data.forEach(user => {
                        html += `
                            <tr>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td>${user.status}</td>
                                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            </tr>
                        `;
                    });
                }
                $('#recent-users').html(html);
            })
            .catch(error => {
                console.error('Error loading recent users:', error);
                $('#recent-users').html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
            });

            // Load recent products
            fetch('/admin/dashboard/recent-products', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="4" class="text-center text-muted">No recent products</td></tr>';
                } else {
                    data.forEach(product => {
                        html += `
                            <tr>
                                <td>${product.title}</td>
                                <td>${product.provider_name}</td>
                                <td>$${parseFloat(product.price).toFixed(2)}</td>
                                <td>${product.status}</td>
                            </tr>
                        `;
                    });
                }
                $('#recent-products').html(html);
            })
            .catch(error => {
                console.error('Error loading recent products:', error);
                $('#recent-products').html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
            });
        }
    </script>
</x-app-layout>
