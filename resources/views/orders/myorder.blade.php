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
                                    <span class="badge rounded-pill fs-6 px-3 py-2 js-order-badge" data-order-id="{{ $order->id }}">
                                        {{ ucfirst($order->order_status) }}
                                    </span>
                                    <div class="mt-2"><strong>Total: ${{ number_format((float)$order->total_amount, 2) }}</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Progress steps -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3 js-order-progress" data-order-id="{{ $order->id }}">
                                    <div class="step">
                                        <span class="badge rounded-pill px-3 py-2 step-badge" data-step="pending">Pending</span>
                                    </div>
                                    <div class="flex-grow-1 border-top" style="opacity:.3"></div>
                                    <div class="step">
                                        <span class="badge rounded-pill px-3 py-2 step-badge" data-step="shipped">Shipped</span>
                                    </div>
                                    <div class="flex-grow-1 border-top" style="opacity:.3"></div>
                                    <div class="step">
                                        <span class="badge rounded-pill px-3 py-2 step-badge" data-step="delivered">Delivered</span>
                                    </div>
                                    <div class="flex-grow-1 border-top" style="opacity:.3"></div>
                                    <div class="step">
                                        <span class="badge rounded-pill px-3 py-2 step-badge" data-step="cancelled">Cancelled</span>
                                    </div>
                                </div>
                            </div>
                            
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
                                <div>
                                    @if($order->order_status === 'pending')
                                        <button class="btn btn-outline-danger btn-sm js-cancel-order" data-order-id="{{ $order->id }}">Cancel Order</button>
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
    (function(){
        const BADGE_CLASSES = {
            pending: 'bg-warning',
            shipped: 'bg-primary',
            delivered: 'bg-success',
            cancelled: 'bg-danger',
            default: 'bg-secondary'
        };

        function setBadge(el, status){
            const s = (status||'').toLowerCase();
            const cls = BADGE_CLASSES[s] || BADGE_CLASSES.default;
            el.className = 'badge rounded-pill fs-6 px-3 py-2 js-order-badge ' + cls;
            el.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        }

        function updateProgress(container, status){
            const s = (status||'').toLowerCase();
            const steps = container.querySelectorAll('.step-badge');
            steps.forEach(function(b){ b.className = 'badge rounded-pill px-3 py-2 step-badge bg-light text-muted'; });
            const order = ['pending','shipped','delivered'];
            if (s === 'cancelled') {
                steps.forEach(function(b){ if (b.dataset.step==='cancelled'){ b.className = 'badge rounded-pill px-3 py-2 step-badge '+BADGE_CLASSES.cancelled; }});
                return;
            }
            order.forEach(function(name){
                const badge = Array.from(steps).find(function(b){ return b.dataset.step===name; });
                if (!badge) return;
                badge.className = 'badge rounded-pill px-3 py-2 step-badge ' + (order.indexOf(name) <= order.indexOf(s) ? (BADGE_CLASSES[name]||BADGE_CLASSES.default) : 'bg-light text-muted');
            });
        }

        function poll(){
            fetch('/user/orders/status', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (!data || !data.success) return;
                    (data.orders||[]).forEach(function(o){
                        var badge = document.querySelector('.js-order-badge[data-order-id="'+o.id+'"]');
                        if (badge) setBadge(badge, o.order_status);
                        var prog = document.querySelector('.js-order-progress[data-order-id="'+o.id+'"]');
                        if (prog) updateProgress(prog, o.order_status);
                        var cancelBtn = document.querySelector('.js-cancel-order[data-order-id="'+o.id+'"]');
                        if (cancelBtn) { cancelBtn.style.display = o.can_cancel ? '' : 'none'; }
                    });
                })
                .catch(function(){ /* ignore */ })
                .finally(function(){ setTimeout(poll, 8000); });
        }

        document.addEventListener('click', function(e){
            var btn = e.target.closest('.js-cancel-order');
            if (!btn) return;
            var orderId = btn.getAttribute('data-order-id');
            btn.disabled = true;
            fetch('/user/orders/'+orderId+'/cancel', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            }).then(function(r){ return r.json(); })
              .then(function(){ poll(); })
              .catch(function(){})
              .finally(function(){ btn.disabled = false; });
        });

        // Initialize current badges and progress
        document.querySelectorAll('.js-order-badge').forEach(function(b){ setBadge(b, b.textContent.trim()); });
        document.querySelectorAll('.js-order-progress').forEach(function(p){
            var orderId = p.getAttribute('data-order-id');
            var badge = document.querySelector('.js-order-badge[data-order-id="'+orderId+'"]');
            if (badge) updateProgress(p, badge.textContent.trim());
        });

        poll();
    })();
</script>
