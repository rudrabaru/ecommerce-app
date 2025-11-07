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
                <div class="row">
                    <div class="col-lg-3 col-md-3">
                    </div>
                    <div class="col-lg-6 col-md-9">
                        <div class="tab-content">
                            <div class="tab-pane active" id="tabs-1" role="tabpanel">
                                <div class="product__details__pic__item">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.onerror=null;this.src='https://placehold.co/600x600?text=%20';">
                                </div>
                            </div>
                            <div class="tab-pane" id="tabs-2" role="tabpanel">
                                <div class="product__details__pic__item">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.onerror=null;this.src='https://placehold.co/600x600?text=%20';">
                                </div>
                            </div>
                            <div class="tab-pane" id="tabs-3" role="tabpanel">
                                <div class="product__details__pic__item">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.onerror=null;this.src='https://placehold.co/600x600?text=%20';">
                                </div>
                            </div>
                            <div class="tab-pane" id="tabs-4" role="tabpanel">
                                <div class="product__details__pic__item">
                                    <img src="{{ asset('img/shop-details/product-big-4.png') }}" alt="">
                                    <a href="https://www.youtube.com/watch?v=8PJ3_p7VqHw&list=RD8PJ3_p7VqHw&start_radio=1" class="video-popup"><i class="fa fa-play"></i></a>
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
                                    <div class="product__details__option__size">
                                        <span>Stock:</span>
                                        <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $product->stock > 0 ? 'In Stock (' . $product->stock . ')' : 'Out of Stock' }}
                                        </span>
                                    </div>
                                </div>
                            <div class="product__details__option">
                                <div class="product__details__option__size">
                                    <span>Size:</span>
                                    <label for="xxl">xxl
                                        <input type="radio" id="xxl">
                                    </label>
                                    <label class="active" for="xl">xl
                                        <input type="radio" id="xl">
                                    </label>
                                    <label for="l">l
                                        <input type="radio" id="l">
                                    </label>
                                    <label for="sm">s
                                        <input type="radio" id="sm">
                                    </label>
                                </div>
                                <div class="product__details__option__color">
                                    <span>Color:</span>
                                    <label class="c-1" for="sp-1">
                                        <input type="radio" id="sp-1">
                                    </label>
                                    <label class="c-2" for="sp-2">
                                        <input type="radio" id="sp-2">
                                    </label>
                                    <label class="c-3" for="sp-3">
                                        <input type="radio" id="sp-3">
                                    </label>
                                    <label class="c-4" for="sp-4">
                                        <input type="radio" id="sp-4">
                                    </label>
                                    <label class="c-9" for="sp-9">
                                        <input type="radio" id="sp-9">
                                    </label>
                                </div>
                            </div>
                            <div class="product__details__cart__option">
                                @if($product->stock > 0)
                                <form method="post" action="{{ route('cart.add') }}" class="d-flex align-items-center add-to-cart-form">
                                    @csrf
                                    <div class="quantity mr-2">
                                        <div class="pro-qty">
                                            <button type="button" class="qtybtn dec">-</button>
                                            <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="quantity-input" readonly>
                                            <button type="button" class="qtybtn inc">+</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="primary-btn add-to-cart-btn">add to cart</button>
                                </form>
                                @else
                                <button class="primary-btn" disabled>Out of Stock</button>
                                @endif
                            </div>
                            <div class="product__details__btns__option">
                                <a href="#"><i class="fa fa-heart"></i> add to wishlist</a>
                                <a href="#"><i class="fa fa-exchange"></i> Add To Compare</a>
                            </div>
                            <div class="product__details__last__option">
                                <h5><span>Guaranteed Safe Checkout</span></h5>
                                <img src="{{ asset('img/shop-details/details-payment.png') }}" alt="">
                                <ul>
                                    <li><span>SKU:</span> {{ $product->id }}</li>
                                    <li><span>Categories:</span> {{ $product->category->name ?? '—' }}</li>
                                    <li><span>Provider:</span> {{ $product->provider->name ?? '—' }}</li>
                                </ul>
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
                                        <p class="note">Nam tempus turpis at metus scelerisque placerat nulla deumantos
                                            solicitud felis. Pellentesque diam dolor, elementum etos lobortis des mollis
                                            ut risus. Sedcus faucibus an sullamcorper mattis drostique des commodo
                                        pharetras loremos.</p>
                                        <div class="product__details__tab__content__item">
                                            <h5>Products Infomation</h5>
                                            <p>A Pocket PC is a handheld computer, which features many of the same
                                                capabilities as a modern PC. These handy little devices allow
                                                individuals to retrieve and store e-mail messages, create a contact
                                                file, coordinate appointments, surf the internet, exchange text messages
                                                and more. Every product that is labeled as a Pocket PC must be
                                                accompanied with specific software to operate the unit and must feature
                                            a touchscreen and touchpad.</p>
                                            <p>As is the case with any new technology product, the cost of a Pocket PC
                                                was substantial during it’s early release. For approximately $700.00,
                                                consumers could purchase one of top-of-the-line Pocket PCs in 2003.
                                                These days, customers are finding that prices have become much more
                                                reasonable now that the newness is wearing off. For approximately
                                            $350.00, a new Pocket PC can now be purchased.</p>
                                        </div>
                                        <div class="product__details__tab__content__item">
                                            <h5>Material used</h5>
                                            <p>Polyester is deemed lower quality due to its none natural quality’s. Made
                                                from synthetic materials, not natural like wool. Polyester suits become
                                                creased easily and are known for not being breathable. Polyester suits
                                                tend to have a shine to them compared to wool and cotton suits, this can
                                                make the suit look cheap. The texture of velvet is luxurious and
                                                breathable. Velvet is a great choice for dinner party jacket and can be
                                            worn all year round.</p>
                                        </div>
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
                                        <p class="note">Nam tempus turpis at metus scelerisque placerat nulla deumantos
                                            solicitud felis. Pellentesque diam dolor, elementum etos lobortis des mollis
                                            ut risus. Sedcus faucibus an sullamcorper mattis drostique des commodo
                                        pharetras loremos.</p>
                                        <div class="product__details__tab__content__item">
                                            <h5>Products Infomation</h5>
                                            <p>A Pocket PC is a handheld computer, which features many of the same
                                                capabilities as a modern PC. These handy little devices allow
                                                individuals to retrieve and store e-mail messages, create a contact
                                                file, coordinate appointments, surf the internet, exchange text messages
                                                and more. Every product that is labeled as a Pocket PC must be
                                                accompanied with specific software to operate the unit and must feature
                                            a touchscreen and touchpad.</p>
                                            <p>As is the case with any new technology product, the cost of a Pocket PC
                                                was substantial during it’s early release. For approximately $700.00,
                                                consumers could purchase one of top-of-the-line Pocket PCs in 2003.
                                                These days, customers are finding that prices have become much more
                                                reasonable now that the newness is wearing off. For approximately
                                            $350.00, a new Pocket PC can now be purchased.</p>
                                        </div>
                                        <div class="product__details__tab__content__item">
                                            <h5>Material used</h5>
                                            <p>Polyester is deemed lower quality due to its none natural quality’s. Made
                                                from synthetic materials, not natural like wool. Polyester suits become
                                                creased easily and are known for not being breathable. Polyester suits
                                                tend to have a shine to them compared to wool and cotton suits, this can
                                                make the suit look cheap. The texture of velvet is luxurious and
                                                breathable. Velvet is a great choice for dinner party jacket and can be
                                            worn all year round.</p>
                                        </div>
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
                    <h3 class="related-title">Related Product</h3>
                </div>
            </div>
            <div class="row">
                @forelse($relatedProducts as $related)
                <div class="col-lg-3 col-md-6 col-sm-6 col-sm-6">
                    <div class="product__item">
                        <div class="product__item__pic set-bg" data-setbg="{{ $related->image ? asset('storage/'.$related->image) : asset('img/product/product-1.jpg') }}">
                            <ul class="product__hover">
                                <li><a href="#"><img src="{{ asset('img/icon/heart.png') }}" alt=""></a></li>
                                <li><a href="#"><img src="{{ asset('img/icon/compare.png') }}" alt=""> <span>Compare</span></a></li>
                                <li><a href="{{ route('shop.details', $related->id) }}"><img src="{{ asset('img/icon/search.png') }}" alt=""></a></li>
                            </ul>
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

    <!-- Search Begin -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search here.....">
            </form>
        </div>
    </div>
    <!-- Search End -->

    <!-- Page-specific styles only; JS plugins are loaded in the global footer -->
    
    <style>
        /* Custom quantity selector styles */
        .pro-qty {
            display: flex;
            align-items: center;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            width: 120px;
        }
        
        .pro-qty input {
            border: none !important;
            text-align: center;
            flex: 1;
            padding: 8px 0;
            background: white;
            font-weight: 600;
        }
        
        .pro-qty .qtybtn {
            background: #f8f9fa;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            transition: all 0.2s;
            font-weight: bold;
            font-size: 16px;
        }
        
        .pro-qty .qtybtn:hover {
            background: #e7ab3c;
            color: white;
        }
        
        .pro-qty .qtybtn.dec {
            border-right: 1px solid #e5e5e5;
        }
        
        .pro-qty .qtybtn.inc {
            border-left: 1px solid #e5e5e5;
        }
    </style>
    
    @include('components.cart-script')
</body>

</html>