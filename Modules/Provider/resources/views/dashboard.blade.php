<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Provider Dashboard</h1>
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
                                    My Products</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-products">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                    Total Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-orders">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
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
                                    Pending Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-orders">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                    Completed Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="completed-orders">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                        <a href="{{ route('provider.orders.index') }}" class="btn btn-sm btn-primary js-ajax-link">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-orders">
                                    <tr>
                                        <td colspan="6" class="text-center">
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

            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">My Products</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="my-products">
                                    <tr>
                                        <td colspan="3" class="text-center">
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

    @push('scripts')
    <script>
        $(document).ready(function() {
            loadDashboardData();
        });

        function loadDashboardData() {
            // Load stats
            fetch('/provider/dashboard/stats', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                $('#total-products').text(data.total_products || 0);
                $('#total-orders').text(data.total_orders || 0);
                $('#pending-orders').text(data.pending_orders || 0);
                $('#completed-orders').text(data.completed_orders || 0);
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });

            // Load recent orders
            fetch('/provider/dashboard/recent-orders', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">No recent orders</td></tr>';
                } else {
                    data.forEach(order => {
                        const statusClass = getStatusClass(order.status);
                        html += `
                            <tr>
                                <td>${order.order_number}</td>
                                <td>${order.customer_name}</td>
                                <td>${order.product_name}</td>
                                <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                                <td><span class="badge ${statusClass}">${order.status}</span></td>
                                <td>${new Date(order.created_at).toLocaleDateString()}</td>
                            </tr>
                        `;
                    });
                }
                $('#recent-orders').html(html);
            })
            .catch(error => {
                console.error('Error loading recent orders:', error);
                $('#recent-orders').html('<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>');
            });

            // Load my products
            fetch('/provider/dashboard/my-products', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="3" class="text-center text-muted">No products yet</td></tr>';
                } else {
                    data.forEach(product => {
                        const statusClass = product.is_approved ? 'badge-success' : 'badge-warning';
                        const statusText = product.is_approved ? 'Approved' : 'Pending';
                        html += `
                            <tr>
                                <td>${product.title}</td>
                                <td>${product.stock}</td>
                                <td><span class="badge ${statusClass}">${statusText}</span></td>
                            </tr>
                        `;
                    });
                }
                $('#my-products').html(html);
            })
            .catch(error => {
                console.error('Error loading my products:', error);
                $('#my-products').html('<tr><td colspan="3" class="text-center text-danger">Error loading data</td></tr>');
            });
        }

        function getStatusClass(status) {
            switch(status) {
                case 'pending': return 'badge-warning';
                case 'confirmed': return 'badge-info';
                case 'shipped': return 'badge-primary';
                case 'delivered': return 'badge-success';
                case 'cancelled': return 'badge-danger';
                default: return 'badge-secondary';
            }
        }
    </script>
    @endpush
</x-app-layout>
