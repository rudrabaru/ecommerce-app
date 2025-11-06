myorder.blade.php :
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
                                        @if($order->order_status === 'delivered' && $item->order_status !== 'cancelled')
                                            <div class="mt-2 js-rating-control"
                                                 data-order-id="{{ $order->id }}"
                                                 data-order-item-id="{{ $item->id }}"
                                                 data-product-id="{{ $item->product_id }}">
                                                <!-- Rating status or Rate button injected by JS -->
                                            </div>
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
                                <div>
                                    @if($order->order_status === 'pending')
                                        <button class="btn btn-outline-danger btn-sm js-cancel-order" data-order-id="{{ $order->id }}">Cancel Order</button>
                                    @elseif($order->order_status === 'delivered')
                                        <button class="btn btn-primary btn-sm js-rate-order-btn" data-order-id="{{ $order->id }}">Rate Now</button>
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

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ratingModalLabel">Rate Your Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ratingModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

        // Rating functionality
        function renderStars(container, currentRating, readOnly, productId, orderItemId) {
            const stars = [];
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('span');
                star.className = 'star-rating-star' + (readOnly ? ' readonly' : ' clickable');
                star.innerHTML = i <= currentRating ? '⭐' : '☆';
                star.dataset.rating = i;
                if (!readOnly) {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        container.dataset.rating = rating;
                        renderStars(container, rating, false, productId, orderItemId);
                    });
                }
                stars.push(star);
            }
            container.innerHTML = '';
            stars.forEach(s => container.appendChild(s));
        }

        function loadRatingControls() {
            document.querySelectorAll('.js-rating-control').forEach(function(ctrl) {
                const orderId = ctrl.dataset.orderId;
                const productId = ctrl.dataset.productId;
                fetch(`/ratings/orders/${orderId}/products/${productId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.rating) {
                        const rating = data.rating;
                        let html = '<div class="small"><strong>Your Rating:</strong><br>';
                        if (rating.rating) {
                            html += '<span class="star-display">';
                            for (let i = 1; i <= 5; i++) {
                                html += i <= rating.rating ? '⭐' : '☆';
                            }
                            html += '</span>';
                        }
                        if (rating.review) {
                            html += '<div class="mt-1"><em>"' + (rating.review.length > 50 ? rating.review.substring(0, 50) + '...' : rating.review) + '"</em></div>';
                        }
                        html += '</div>';
                        ctrl.innerHTML = html;
                    } else {
                        ctrl.innerHTML = '<button class="btn btn-sm btn-outline-primary js-rate-item-btn" data-order-id="' + orderId + '" data-product-id="' + productId + '" data-order-item-id="' + ctrl.dataset.orderItemId + '">Rate</button>';
                    }
                })
                .catch(() => {
                    ctrl.innerHTML = '<button class="btn btn-sm btn-outline-primary js-rate-item-btn" data-order-id="' + orderId + '" data-product-id="' + productId + '" data-order-item-id="' + ctrl.dataset.orderItemId + '">Rate</button>';
                });
            });
        }

        // Rate Order button (opens modal)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-rate-order-btn');
            if (btn) {
                const orderId = btn.dataset.orderId;
                const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
                const body = document.getElementById('ratingModalBody');
                body.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';
                modal.show();

                fetch(`/ratings/orders/${orderId}/eligible-products`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async function(r){
                    try { return await r.json(); } catch(e) { return { success: r.ok }; }
                })
                .then(function(data){
                    if (!data.success) {
                        body.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to load products') + '</div>';
                        return;
                    }

                    let html = '';
                    data.products.forEach(function(p) {
                        html += '<div class="border rounded p-3 mb-3" data-product-id="' + p.product_id + '" data-order-item-id="' + p.order_item_id + '">';
                        html += '<div class="d-flex align-items-center mb-3">';
                        html += '<img src="' + (p.product_image || 'https://placehold.co/60x60') + '" style="width:60px;height:60px;object-fit:cover;border-radius:6px;" class="me-3">';
                        html += '<div><strong>' + p.product_title + '</strong><br><small>Qty: ' + p.quantity + '</small></div>';
                        html += '</div>';

                        if (p.rating) {
                            html += '<div class="alert alert-info"><strong>Your Rating:</strong><br>';
                            if (p.rating.rating) {
                                html += '<span class="star-display">';
                                for (let i = 1; i <= 5; i++) {
                                    html += i <= p.rating.rating ? '⭐' : '☆';
                                }
                                html += '</span>';
                            }
                            if (p.rating.review) {
                                html += '<div class="mt-2"><em>"' + p.rating.review + '"</em></div>';
                            }
                            html += '</div>';
                        } else {
                            html += '<div><label class="form-label">Star Rating (Optional)</label>';
                            html += '<div class="star-rating-container mb-2" data-rating="0"></div>';
                            html += '<label class="form-label">Review (Optional)</label>';
                            html += '<textarea class="form-control review-text" rows="3" placeholder="Write your review..."></textarea>';
                            html += '</div>';
                        }
                        html += '</div>';
                    });

                    // Add single submit button for all
                    html += '<div class="d-flex justify-content-end"><button class="btn btn-primary js-submit-all-ratings" data-order-id="' + orderId + '">Submit</button></div>';
                    body.innerHTML = html;

                    // Initialize star ratings for products that can be rated
                    body.querySelectorAll('.star-rating-container').forEach(function(container) {
                        renderStars(container, 0, false, container.closest('[data-product-id]').dataset.productId, container.closest('[data-order-item-id]').dataset.orderItemId);
                    });
                })
                .catch(function(){
                    body.innerHTML = '<div class="alert alert-danger">Error loading products</div>';
                });
            }

            // Rate individual item button
            const rateItemBtn = e.target.closest('.js-rate-item-btn');
            if (rateItemBtn) {
                const orderId = rateItemBtn.dataset.orderId;
                const btn = document.querySelector('.js-rate-order-btn[data-order-id="' + orderId + '"]');
                if (btn) btn.click();
            }

            // Submit all ratings (batch)
            const submitAny = e.target.closest('.js-submit-all-ratings');
            if (submitAny) {
                const bodyEl = document.getElementById('ratingModalBody');
                const blocks = bodyEl.querySelectorAll('[data-product-id][data-order-item-id]');
                const orderId = submitAny.dataset.orderId;
                const items = [];
                blocks.forEach(function(b){
                    const star = b.querySelector('.star-rating-container');
                    const review = b.querySelector('.review-text');
                    const rating = star ? parseInt(star.dataset.rating || '0') : 0;
                    const text = review ? (review.value || '').trim() : '';
                    items.push({
                        product_id: b.getAttribute('data-product-id'),
                        order_item_id: b.getAttribute('data-order-item-id'),
                        rating: rating > 0 ? rating : null,
                        review: text !== '' ? text : null,
                    });
                });
                submitAny.disabled = true;
                submitAny.textContent = 'Submitting...';
                fetch('/ratings/submit-batch', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, items })
                })
                .then(async function(r){
                    try { return await r.json(); } catch(e) { return { success: r.ok }; }
                })
                .then(function(data){
                    if (data && data.success) {
                        Swal.fire('Success', data.message || 'Ratings submitted successfully', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        Swal.fire('Error', (data && data.message) || 'Failed to submit ratings', 'error');
                        submitAny.disabled = false;
                        submitAny.textContent = 'Submit';
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'An error occurred', 'error');
                    submitAny.disabled = false;
                    submitAny.textContent = 'Submit';
                });
            }
        });

        // Load rating controls on page load
        loadRatingControls();
    })();
</script>
