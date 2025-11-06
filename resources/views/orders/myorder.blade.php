<x-header />

<x-breadcrumbs :items="[
    ['label' => 'Home', 'route' => route('home')],
    ['label' => 'My Orders']
]" />

<style>
    .star-rating-star {
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-block;
    }
    .star-rating-star.clickable:hover {
        transform: scale(1.2);
    }
    .star-rating-star.readonly {
        cursor: default;
    }
    .star-display {
        font-size: 1.1rem;
        color: #ffc107;
    }
    .review-char-counter {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    .review-char-counter.warning {
        color: #dc3545;
        font-weight: 600;
    }
    .your-reviews-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .review-item {
        background: white;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border: 1px solid #e9ecef;
    }
    .review-item:last-child {
        margin-bottom: 0;
    }
    .product-rating-inline {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    /* ✅ ADD THESE NEW RULES BELOW */
    .js-toggle-review {
        text-decoration: none;
        font-weight: 500;
        cursor: pointer;
    }
    .js-toggle-review:hover {
        text-decoration: underline;
    }
    .review-item button i {
        font-size: 0.875rem;
    }
    .review-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.35rem;
        margin-top: 0.5rem;
    }

    .icon-btn {
        background: #ffffff;
        border: 1px solid #d1d1d1;
        border-radius: 6px;
        padding: 6px 9px;
        cursor: pointer;
        transition: 0.2s;
    }

    .icon-btn:hover {
        background: #f2f2f2;
    }

    .icon-btn i {
        font-size: 0.9rem;
        color: #555;
    }
</style>


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
                            
                            <!-- Your Reviews section -->
                            <div id="yourReviews-{{ $order->id }}" class="your-reviews-section" style="display:none;"></div>

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
                                        <button class="btn btn-primary btn-sm js-rate-order-btn" data-order-id="{{ $order->id }}" style="display:none;">Rate Your Products</button>
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
                <button type="button" class="btn-close js-modal-close" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ratingModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
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
                    // Hover effect
                    star.addEventListener('mouseenter', function() {
                        const hoverRating = parseInt(this.dataset.rating);
                        container.querySelectorAll('.star-rating-star').forEach(function(s, idx) {
                            s.innerHTML = (idx + 1) <= hoverRating ? '⭐' : '☆';
                        });
                    });
                    
                    // Click to set rating
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        container.dataset.rating = rating;
                    });
                }
                stars.push(star);
            }
            
            container.innerHTML = '';
            stars.forEach(s => container.appendChild(s));
            
            if (!readOnly) {
                // Reset on mouse leave
                container.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(this.dataset.rating || '0');
                    this.querySelectorAll('.star-rating-star').forEach(function(s, idx) {
                        s.innerHTML = (idx + 1) <= currentRating ? '⭐' : '☆';
                    });
                });
            }
        }

        function setupCharCounter(textarea) {
            const maxChars = 50;
            const counter = document.createElement('div');
            counter.className = 'review-char-counter';
            textarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const remaining = maxChars - textarea.value.length;
                counter.textContent = textarea.value.length + '/' + maxChars;
                counter.className = 'review-char-counter' + (remaining < 10 ? ' warning' : '');
            }
            
            textarea.addEventListener('input', function() {
                if (this.value.length > maxChars) {
                    this.value = this.value.substring(0, maxChars);
                }
                updateCounter();
            });
            
            updateCounter();
        }

        // Track ratings per order
        const orderRatingsMap = {};

        function loadRatingControls() {
            document.querySelectorAll('.js-rating-control').forEach(function(ctrl) {
                const orderId = ctrl.dataset.orderId;
                const productId = ctrl.dataset.productId;
                
                if (!orderRatingsMap[orderId]) {
                    orderRatingsMap[orderId] = { total: 0, rated: 0, reviews: [] };
                }
                orderRatingsMap[orderId].total++;
                
                fetch(`/ratings/orders/${orderId}/products/${productId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                         if (data.success && data.rating) {
                         const rating = data.rating;
                         orderRatingsMap[orderId].rated++;
                         
                         // Show simple status only; no star/review here
                         ctrl.innerHTML = '<span class="badge bg-success-subtle text-success">Already Rated</span>';
                        
                        // Store for reviews section
                        orderRatingsMap[orderId].reviews.push({
                            productTitle: ctrl.closest('.border-bottom').querySelector('.fw-semibold').textContent,
                             rating: rating.rating,
                             review: rating.review,
                             id: rating.id
                        });
                        
                        updateRateButton(orderId);
                        updateReviewsSection(orderId);
                    } else {
                        ctrl.innerHTML = '<button class="btn btn-sm btn-outline-primary js-rate-item-btn" data-order-id="' + orderId + '" data-product-id="' + productId + '" data-order-item-id="' + ctrl.dataset.orderItemId + '">Rate</button>';
                        updateRateButton(orderId);
                    }
                })
                .catch(() => {
                    ctrl.innerHTML = '<button class="btn btn-sm btn-outline-primary js-rate-item-btn" data-order-id="' + orderId + '" data-product-id="' + productId + '" data-order-item-id="' + ctrl.dataset.orderItemId + '">Rate</button>';
                    updateRateButton(orderId);
                });
            });
        }

        function updateRateButton(orderId) {
            const info = orderRatingsMap[orderId];
            if (!info) return;
            
            const btn = document.querySelector('.js-rate-order-btn[data-order-id="' + orderId + '"]');
            if (!btn) return;
            
            // Show button only if not all items are rated
            if (info.rated < info.total) {
                btn.style.display = '';
            } else {
                btn.style.display = 'none';
            }
        }

        function updateReviewsSection(orderId) {
            const info = orderRatingsMap[orderId];
            if (!info || info.reviews.length === 0) return;
            
            const section = document.getElementById('yourReviews-' + orderId);
            if (!section) return;
            
            let html = '<h6 class="mb-3">Your Reviews</h6>';
            info.reviews.forEach(function(rev) {
                const full = (rev.review||'');
                const isLong = full.length > 15;
                const short = isLong ? (full.substring(0,15) + '...') : full;
                html += '<div class="review-item" data-rating-id="'+ (rev.id||'') +'" data-full-review="' + (full||'').replace(/"/g, '&quot;') + '" data-rating-value="' + (rev.rating||'') + '">';
                html += '<div class="fw-semibold mb-1">' + rev.productTitle + '</div>';
                if (rev.rating) {
                    html += '<div class="star-display mb-1">';
                    for (let i = 1; i <= 5; i++) {
                        html += i <= rev.rating ? '⭐' : '☆';
                    }
                    html += '</div>';
                }
                if (full) {
                    html += '<div class="text-muted fst-italic small">"<span class="js-review-text">' + (short||'') + '</span>"';
                    if (isLong) { html += ' <a href="#" class="js-toggle-review text-primary">read more</a>'; }
                    html += '</div>';
                }
                // Actions
                html += '<div class="text-end mt-2 review-actions">';
                if (rev.id) {
                    html += '<button type="button" class="icon-btn js-edit-review" title="Edit" data-rating-id="'+rev.id+'"><i class="fa fa-pencil"></i></button>';
                    html += '<button type="button" class="icon-btn js-delete-review" title="Delete" data-rating-id="'+rev.id+'"><i class="fa fa-trash"></i></button>';
                }
                html += '</div>';
                html += '</div>';
            });
            
            section.innerHTML = html;
            section.style.display = 'block';
        }

        // Modal close handlers
        function closeModal() {
            const modalEl = document.getElementById('ratingModal');
            try {
                if (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                        return;
                    }
                }
                // Fallback manual close
                modalEl.classList.remove('show');
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.setAttribute('aria-modal', 'false');
                modalEl.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            } catch (e) {
                console.error('Error closing modal:', e);
            }
        }

        document.addEventListener('click', function(e) {
            // Toggle review text (read more/less)
            const toggleLink = e.target.closest('.js-toggle-review');
            if (toggleLink) {
                e.preventDefault();
                const wrap = toggleLink.closest('.review-item');
                const textEl = wrap.querySelector('.js-review-text');
                const fullReview = wrap.getAttribute('data-full-review') || '';
                const isExpanded = toggleLink.textContent.trim() === 'read less';
                
                if (isExpanded) {
                    // Collapse
                    const short = fullReview.length > 15 ? (fullReview.substring(0,15) + '...') : fullReview;
                    textEl.textContent = short;
                    toggleLink.textContent = 'read more';
                } else {
                    // Expand
                    textEl.textContent = fullReview;
                    toggleLink.textContent = 'read less';
                }
                return;
            }

            // Edit review
            const editBtn = e.target.closest('.js-edit-review');
            if (editBtn) {
                e.preventDefault();
                const ratingId = editBtn.getAttribute('data-rating-id');
                const wrap = editBtn.closest('.review-item');
                const currentRating = wrap.getAttribute('data-rating-value') || '';
                const currentReview = wrap.getAttribute('data-full-review') || '';
                
                Swal.fire({
                    title: 'Edit Review',
                    html: '<div class="text-start mb-3">' +
                        '<label class="form-label fw-semibold">Star Rating (Optional)</label>' +
                        '<select id="swal-rating" class="form-select">' +
                        '<option value="">No rating</option>' +
                        '<option value="1"'+(currentRating=='1'?' selected':'')+'>⭐ 1 Star</option>' +
                        '<option value="2"'+(currentRating=='2'?' selected':'')+'>⭐⭐ 2 Stars</option>' +
                        '<option value="3"'+(currentRating=='3'?' selected':'')+'>⭐⭐⭐ 3 Stars</option>' +
                        '<option value="4"'+(currentRating=='4'?' selected':'')+'>⭐⭐⭐⭐ 4 Stars</option>' +
                        '<option value="5"'+(currentRating=='5'?' selected':'')+'>⭐⭐⭐⭐⭐ 5 Stars</option>' +
                        '</select>' +
                        '</div>' +
                        '<div class="text-start">' +
                        '<label class="form-label fw-semibold">Review (Optional, max 50 characters)</label>' +
                        '<textarea id="swal-review" class="form-control" rows="3" maxlength="50" placeholder="Update your review...">' + currentReview + '</textarea>' +
                        '<div id="swal-char-counter" class="text-muted small mt-1">' + currentReview.length + '/50</div>' +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Save Changes',
                    cancelButtonText: 'Cancel',
                    width: '600px',
                    focusConfirm: false,
                    didOpen: () => {
                        const textarea = document.getElementById('swal-review');
                        const counter = document.getElementById('swal-char-counter');
                        textarea.addEventListener('input', function() {
                            if (this.value.length > 50) {
                                this.value = this.value.substring(0, 50);
                            }
                            counter.textContent = this.value.length + '/50';
                            counter.className = 'small mt-1 ' + (this.value.length >= 40 ? 'text-danger fw-bold' : 'text-muted');
                        });
                    },
                    preConfirm: () => {
                        const rating = document.getElementById('swal-rating').value || null;
                        const review = document.getElementById('swal-review').value.trim() || null;
                        return { rating, review };
                    }
                }).then(function(result){
                    if (!result.isConfirmed) return;
                    const payload = result.value || {};
                    
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    fetch('/ratings/' + ratingId + '/update', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: 'Your review has been updated successfully.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            Swal.fire('Error', (data && data.message) || 'Failed to update review', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error','Failed to update review','error'));
                });
                return;
            }

            // Delete review
            const delBtn = e.target.closest('.js-delete-review');
            if (delBtn) {
                e.preventDefault();
                const ratingId = delBtn.getAttribute('data-rating-id');
                
                Swal.fire({
                    title: 'Delete Review?',
                    text: 'Are you sure you want to delete this review? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d'
                }).then(function(res){
                    if (!res.isConfirmed) return;
                    
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    fetch('/ratings/' + ratingId, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Your review has been deleted successfully.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            Swal.fire('Error', (data && data.message) || 'Failed to delete review', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error','Failed to delete review','error'));
                });
                return;
            }

            const closeBtn = e.target.closest('.js-modal-close');
            if (closeBtn) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
                return false;
            }
        });

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
                        html += '<div><strong>' + p.product_title + '</strong><br><small class="text-muted">Qty: ' + p.quantity + '</small></div>';
                        html += '</div>';

                        if (p.rating) {
                            html += '<div class="alert alert-info"><strong>Already Rated</strong><br>';
                            if (p.rating.rating) {
                                html += '<div class="star-display mb-2">';
                                for (let i = 1; i <= 5; i++) {
                                    html += i <= p.rating.rating ? '⭐' : '☆';
                                }
                                html += '</div>';
                            }
                            if (p.rating.review) {
                                html += '<div class="fst-italic">"' + p.rating.review + '"</div>';
                            }
                            html += '</div>';
                        } else {
                            html += '<div><label class="form-label fw-semibold">Star Rating (Optional)</label>';
                            html += '<div class="star-rating-container mb-3" data-rating="0"></div>';
                            html += '<label class="form-label fw-semibold">Review (Optional, max 50 characters)</label>';
                            html += '<textarea class="form-control review-text" rows="3" maxlength="50" placeholder="Share your thoughts..."></textarea>';
                            html += '</div>';
                        }
                        html += '</div>';
                    });

                    html += '<div class="d-flex justify-content-end gap-2">';
                    html += '<button type="button" class="btn btn-secondary js-modal-close">Close</button>';
                    html += '<button class="btn btn-primary js-submit-all-ratings" data-order-id="' + orderId + '">Submit Ratings</button>';
                    html += '</div>';
                    body.innerHTML = html;

                    body.querySelectorAll('.star-rating-container').forEach(function(container) {
                        renderStars(container, 0, false, container.closest('[data-product-id]').dataset.productId, container.closest('[data-order-item-id]').dataset.orderItemId);
                    });
                    
                    body.querySelectorAll('.review-text').forEach(function(textarea) {
                        setupCharCounter(textarea);
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
                const productId = rateItemBtn.dataset.productId;
                const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
                const body = document.getElementById('ratingModalBody');
                body.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';
                modal.show();
                
                fetch(`/ratings/orders/${orderId}/eligible-products?product_id=${productId}`, { 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { 
                        body.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to load product') + '</div>'; 
                        return; 
                    }
                    
                    let html = '';
                    data.products.forEach(function(p){
                        html += '<div class="border rounded p-3 mb-3" data-product-id="' + p.product_id + '" data-order-item-id="' + p.order_item_id + '">';
                        html += '<div class="d-flex align-items-center mb-3">';
                        html += '<img src="' + (p.product_image || 'https://placehold.co/60x60') + '" style="width:60px;height:60px;object-fit:cover;border-radius:6px;" class="me-3">';
                        html += '<div><strong>' + p.product_title + '</strong><br><small class="text-muted">Qty: ' + p.quantity + '</small></div>';
                        html += '</div>';
                        
                        if (p.rating) {
                            html += '<div class="alert alert-info"><strong>Already Rated</strong><br>';
                            if (p.rating.rating) {
                                html += '<div class="star-display mb-2">';
                                for (let i = 1; i <= 5; i++) { 
                                    html += i <= p.rating.rating ? '⭐' : '☆'; 
                                }
                                html += '</div>';
                            }
                            if (p.rating.review) { 
                                html += '<div class="fst-italic">"' + p.rating.review + '"</div>'; 
                            }
                            html += '</div>';
                        } else {
                            html += '<div><label class="form-label fw-semibold">Star Rating (Optional)</label>';
                            html += '<div class="star-rating-container mb-3" data-rating="0"></div>';
                            html += '<label class="form-label fw-semibold">Review (Optional, max 50 characters)</label>';
                            html += '<textarea class="form-control review-text" rows="3" maxlength="50" placeholder="Share your thoughts..."></textarea>';
                            html += '</div>';
                        }
                        html += '</div>';
                    });
                    
                    html += '<div class="d-flex justify-content-end gap-2">';
                    html += '<button type="button" class="btn btn-secondary js-modal-close">Close</button>';
                    html += '<button class="btn btn-primary js-submit-all-ratings" data-order-id="' + orderId + '">Submit Rating</button>';
                    html += '</div>';
                    body.innerHTML = html;
                    
                    body.querySelectorAll('.star-rating-container').forEach(function(container){ 
                        renderStars(container, 0, false, container.closest('[data-product-id]').dataset.productId, container.closest('[data-order-item-id]').dataset.orderItemId); 
                    });
                    
                    body.querySelectorAll('.review-text').forEach(function(textarea) {
                        setupCharCounter(textarea);
                    });
                })
                .catch(() => { 
                    body.innerHTML = '<div class="alert alert-danger">Error loading product</div>'; 
                });
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
                const originalText = submitAny.textContent;
                submitAny.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
                
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message || 'Ratings submitted successfully',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        closeModal();
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: (data && data.message) || 'Failed to submit ratings'
                        });
                        submitAny.disabled = false;
                        submitAny.textContent = originalText;
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while submitting'
                    });
                    submitAny.disabled = false;
                    submitAny.textContent = originalText;
                });
            }
        });

        // Load rating controls on page load
        loadRatingControls();
    })();
</script>