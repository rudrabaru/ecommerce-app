<x-header />

<section class="checkout spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="mb-4">My Orders</h3>
                @forelse($orders as $order)
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Order #{{ $order->order_number }}</h5>
                                    <small class="text-muted">Placed on {{ $order->created_at->format('F j, Y \a\t g:i A') }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill {{ $order->getStatusBadgeClass() }} fs-6 px-3 py-2">
                                        {{ $order->getStatusDisplayName() }}
                                    </span>
                                    <div class="mt-2"><strong>Total: ${{ number_format((float)$order->total_amount, 2) }}</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Order Timeline -->
                            <x-order-timeline :order="$order" />
                            
                            <hr>
                            
                            <!-- Order Items -->
                            <h6 class="mb-3">Order Items:</h6>
                            @foreach($order->orderItems as $item)
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <div class="me-3">
                                            @if($item->product && $item->product->image_url)
                                                <img src="{{ $item->product->image_url }}" 
                                                     alt="{{ $item->product->title }}" 
                                                     style="width:60px;height:60px;object-fit:cover;border-radius:6px;"
                                                     onerror="this.src='https://placehold.co/60x60?text=Product'">
                                            @else
                                                <div style="width:60px;height:60px;background:#f8f8f8;border-radius:6px;"></div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ optional($item->product)->title ?? 'Product' }}</div>
                                            <small class="text-muted">Qty: {{ $item->quantity }} × ${{ number_format((float)$item->unit_price, 2) }}</small>
                                            @if(auth()->user()->hasRole('user'))
                                                <div class="mt-1">
                                                    <span class="badge {{ $item->getStatusBadgeClass() }} badge-sm">
                                                        {{ $item->getStatusDisplayName() }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">${{ number_format((float)$item->total, 2) }}</div>
                                        @if(auth()->user()->hasRole('user') && $item->order_status === 'pending')
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm mt-2" 
                                                    onclick="cancelOrderItem({{ $order->id }}, {{ $item->id }})"
                                                    id="cancelItemBtn{{ $item->id }}">
                                                <i class="fas fa-times"></i> Cancel Item
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($order->shipping_address)
                                <div class="mt-3">
                                    <strong>Shipping Address:</strong>
                                    <p class="mb-0">{{ $order->shipping_address }}</p>
                                </div>
                            @endif
                            
                            @if($order->notes)
                                <div class="mt-2">
                                    <strong>Notes:</strong>
                                    <p class="mb-0">{{ $order->notes }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    @if($order->paymentMethod)
                                        <small class="text-muted">Payment: {{ $order->paymentMethod->display_name ?? $order->paymentMethod->name }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h5>No orders yet</h5>
                        <p class="text-muted">You haven't placed any orders.</p>
                        <a href="{{ route('shop') }}" class="btn btn-primary">Start Shopping</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<x-footer />

<script>
function cancelOrder(orderId) {
    const btn = document.getElementById('cancelBtn' + orderId);
    const originalText = btn.innerHTML;
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Cancel Order?',
            text: 'Are you sure you want to cancel this order? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Cancelling...';
                
                fetch('/user/orders/' + orderId + '/cancel', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Cancelled',
                            text: data.message || 'Your order has been cancelled successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to cancel order', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred while cancelling the order', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
        });
    } else {
        if (confirm('Are you sure you want to cancel this order?')) {
            btn.disabled = true;
            fetch('/user/orders/' + orderId + '/cancel', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to cancel order');
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                btn.disabled = false;
            });
        }
    }
}

function cancelOrderItem(orderId, itemId) {
    const btn = document.getElementById('cancelItemBtn' + itemId);
    const originalText = btn.innerHTML;
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Cancel Item?',
            text: 'Are you sure you want to cancel this item? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Cancelling...';
                
                fetch('/user/orders/' + orderId + '/items/' + itemId + '/cancel', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Item Cancelled',
                            text: data.message || 'The item has been cancelled successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to cancel item', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred while cancelling the item', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
        });
    } else {
        if (confirm('Are you sure you want to cancel this item?')) {
            btn.disabled = true;
            fetch('/user/orders/' + orderId + '/items/' + itemId + '/cancel', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item cancelled successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to cancel item');
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                btn.disabled = false;
            });
        }
    }
}

window.cancelOrder = cancelOrder;
window.cancelOrderItem = cancelOrderItem;
</script>
