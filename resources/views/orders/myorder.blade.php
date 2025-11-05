<x-header />

<x-breadcrumbs :items="[
    ['label' => 'Home', 'route' => route('home')],
    ['label' => 'My Orders']
]" />

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
                                    <span class="badge rounded-pill bg-secondary fs-6 px-3 py-2">N/A</span>
                                    <div class="mt-2"><strong>Total: ${{ number_format((float)$order->total_amount, 2) }}</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Order tracking removed -->
                            
                            <hr>
                            
                            <!-- Order Items -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Order Items:</h6>
                            </div>
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
                                            
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">${{ number_format((float)$item->total, 2) }}</div>
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
// Order tracking removed on user side
</script>
