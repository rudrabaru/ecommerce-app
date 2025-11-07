<x-header />

    <!-- Shop Details Section Begin -->
    <section class="shop-details">
        <div class="product__details__pic">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="product__details__breadcrumb">
                            <a href="{{ url('/') }}">Home</a>
                            <a href="{{ route('shop') }}">Shop</a>
                            <span>Product Details</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Product Image Section - Beneath Breadcrumb -->
        <div class="product__details__pic">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-9 offset-lg-3">
                        <div class="tab-content">
                            <div class="tab-pane active" id="tabs-1" role="tabpanel">
                                <div class="product__details__pic__item">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.onerror=null;this.src='https://placehold.co/600x600?text=%20';">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="product__details__content">
            <div class="container">
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-8">
                            <div class="product__details__text">
                                <h4>{{ $product->title }}</h4>
                                <div class="rating">
                                    @if($product->average_rating)
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fa {{ $i <= round($product->average_rating) ? 'fa-star' : 'fa-star-o' }}"></i>
                                        @endfor
                                        <span> - {{ $product->review_count }} {{ $product->review_count == 1 ? 'Review' : 'Reviews' }}</span>
                                    @else
                                        <span class="text-muted">No ratings yet</span>
                                    @endif
                                </div>
                                <h3>${{ number_format((float)$product->price, 2) }}</h3>
                                <p>{{ $product->description }}</p>
                                <div class="product__details__option">
                                    <div id="stock-status" class="mt-2">
                                    <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}" id="stock-badge">
                                        {{ $product->stock > 0 ? 'In Stock (' . $product->stock . ')' : 'Out of Stock' }}
                                    </span>
                                </div>
                            </div>
                            <div class="product__details__cart__option">
                                <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form" id="product-cart-form">
                                    @csrf
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="quantity-wrapper">
                                            <div class="pro-qty">
                                                <button type="button" class="qtybtn dec">-</button>
                                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="quantity-input" id="product-quantity" readonly>
                                                <button type="button" class="qtybtn inc">+</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="product_id" value="{{ $product->id }}" id="product-id">
                                        <button type="submit" class="primary-btn add-to-cart-btn" id="add-to-cart-btn">
                                            <i class="fa fa-shopping-cart me-2"></i>Add to Cart
                                        </button>
                                    </div>
                                </form>
                                
                            </div>
                            
                            <!-- Discount Codes Section -->
                            <x-discount-codes :discountCodes="$discountCodes" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="product__details__tab">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#tabs-5"
                                    role="tab">Description</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tabs-6" role="tab">Customer Reviews
                                    ({{ $reviews->count() }})</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tabs-7" role="tab">Additional
                                    information</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="tabs-5" role="tabpanel">
                                    <div class="product__details__tab__content">
                                        <p class="note">{{ $product->description ?: 'No description available.' }}</p>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-6" role="tabpanel">
                                    <div class="product__details__tab__content">
                                        <div class="mb-4">
                                            <h5>Product Ratings</h5>
                                            @if($product->average_rating)
                                                <div class="mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="display-6 me-3">{{ number_format($product->average_rating, 1) }}</span>
                                                        <div>
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="fa {{ $i <= round($product->average_rating) ? 'fa-star text-warning' : 'fa-star-o' }}"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                    <p class="text-muted mb-0">Based on {{ $product->review_count }} {{ $product->review_count == 1 ? 'review' : 'reviews' }}</p>
                                                </div>
                                            @else
                                                <p class="text-muted">No ratings yet for this product.</p>
                                            @endif
                                        </div>
                                        
                                        @if($reviews->count() > 0)
                                            <h5 class="mb-3">Customer Reviews</h5>
                                            @foreach($reviews as $review)
                                                <div class="product__details__tab__content__item border-bottom pb-3 mb-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <strong>{{ $review->user->name ?? 'Anonymous' }}</strong>
                                                            @if($review->rating)
                                                                <div class="mt-1">
                                                                    @for($i = 1; $i <= 5; $i++)
                                                                        <i class="fa {{ $i <= $review->rating ? 'fa-star text-warning' : 'fa-star-o' }}"></i>
                                                                    @endfor
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted">{{ $review->created_at->format('M d, Y') }}</small>
                                                    </div>
                                                    @if($review->review)
                                                        <p class="mb-0">{{ $review->review }}</p>
                                                    @endif
                                        </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-7" role="tabpanel">
                                    <div class="product__details__tab__content">
                                        <ul>
                                            <li><span>SKU:</span> {{ $product->id }}</li>
                                            <li><span>Category:</span> {{ $product->category->name ?? '—' }}</li>
                                            <li><span>Provider:</span> {{ $product->provider->name ?? '—' }}</li>
                                            <li><span>Stock:</span> {{ $product->stock }}</li>
                                            <li><span>Price:</span> ${{ number_format((float)$product->price, 2) }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shop Details Section End -->

    <!-- Related Section Begin -->
    <section class="related spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="related-title">Related Products</h3>
                </div>
            </div>
            <div class="row">
                @forelse($relatedProducts as $related)
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="product__item">
                        <div class="product__item__pic set-bg" data-setbg="{{ $related->image_url }}" style="background-image:url('{{ $related->image_url }}');">
                        </div>
                        <div class="product__item__text">
                            <h6>{{ $related->title }}</h6>
                            <a href="{{ route('shop.details', $related->id) }}" class="add-cart">View Details</a>
                            <div class="rating">
                                @if($related->average_rating)
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa {{ $i <= round($related->average_rating) ? 'fa-star text-warning' : 'fa-star-o' }}"></i>
                                    @endfor
                                    <span class="ms-1">{{ number_format($related->average_rating, 1) }} ({{ $related->review_count }})</span>
                                @else
                                    <span class="text-muted">No ratings yet</span>
                                @endif
                            </div>
                            <h5>${{ number_format((float)$related->price, 1) }}</h5>
                            <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $related->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-dark mt-2 add-to-cart-btn">+ Add To Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-3">
                        <p>No related products found.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- Related Section End -->

    <x-footer />

    <!-- Page-specific styles only; JS plugins are loaded in the global footer -->
    
    <style>
        /* Enhanced product details cart section */
        .product__details__cart__option {
            margin: 25px 0;
        }
        
        .product__details__cart__option .d-flex {
            align-items: center;
            gap: 20px;
        }
        
        .quantity-wrapper {
            flex-shrink: 0;
        }
        
        /* Custom quantity selector styles */
        .pro-qty {
            display: flex;
            align-items: center;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            width: 140px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .pro-qty input {
            border: none !important;
            text-align: center;
            flex: 1;
            padding: 12px 0;
            background: white;
            font-weight: 600;
            font-size: 16px;
        }
        
        .pro-qty .qtybtn {
            background: #f8f9fa;
            border: none;
            padding: 12px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 45px;
            transition: all 0.2s;
            font-weight: bold;
            font-size: 18px;
            color: #333;
        }
        
        .pro-qty .qtybtn:hover:not(:disabled) {
            background: #e7ab3c;
            color: white;
        }
        
        .pro-qty .qtybtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pro-qty .qtybtn.dec {
            border-right: 1px solid #e5e5e5;
        }
        
        .pro-qty .qtybtn.inc {
            border-left: 1px solid #e5e5e5;
        }
        
        .primary-btn {
            padding: 12px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.3s;
            white-space: nowrap;
            background: #e7ab3c;
            color: white;
            border: none;
        }
        
        .primary-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.4);
            background: #d69a2b;
        }
        
        .primary-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            background: #6c757d;
        }
        
        #stock-status {
            font-size: 14px;
            margin-top: 10px;
        }
        
        #stock-badge {
            font-size: 13px;
            padding: 6px 12px;
        }
        
        @media (max-width: 576px) {
            .product__details__cart__option .d-flex {
                flex-direction: column;
                align-items: stretch;
            }
            
            .quantity-wrapper {
                width: 100%;
            }
            
            .pro-qty {
                width: 100%;
            }
            
            .primary-btn {
                width: 100%;
            }
        }
    </style>
    
    <script>
        // Pass stock URL to JavaScript
        window.productStockUrl = '{{ route("products.stock", $product->id) }}';
    </script>
    <script src="{{ asset('js/product-stock-updater.js') }}"></script>
    @include('components.cart-script')
</body>

</html>