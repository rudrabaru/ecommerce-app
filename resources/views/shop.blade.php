<x-header />

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Shop</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}">Home</a>
                            <span>Shop</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Shop Section Begin -->
    <section class="shop spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="shop__sidebar">
                        <div class="shop__sidebar__search">
                            <form action="{{ route('shop') }}" method="GET">
                                <input type="text" name="q" placeholder="Search..." value="{{ request('q') }}">
                                <button type="submit"><span class="icon_search"></span></button>
                            </form>
                        </div>
                        <div class="shop__sidebar__accordion">
                            <div class="accordion" id="accordionExample">
                                <div class="card">
                                    <div class="card-heading">
                                        <a data-toggle="collapse" data-target="#collapseOne">Categories</a>
                                    </div>
                                    <div id="collapseOne" class="collapse show" data-parent="#accordionExample">
                                        <div class="card-body">
                                            <div class="shop__sidebar__categories">
                                                <ul class="nice-scroll">
                                                    <li><a href="{{ route('shop') }}" class="{{ !request('category') ? 'active' : '' }}">All Categories</a></li>
                                                    @isset($categories)
                                                        @foreach($categories as $category)
                                                            <li><a href="{{ route('shop', array_merge(request()->query(), ['category' => $category->id])) }}" class="{{ request('category') == $category->id ? 'active' : '' }}">{{ $category->name }}</a></li>
                                                        @endforeach
                                                    @endisset
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-heading">
                                        <a data-toggle="collapse" data-target="#collapseThree">Filter Price</a>
                                    </div>
                                    <div id="collapseThree" class="collapse show" data-parent="#accordionExample">
                                        <div class="card-body">
                                            <div class="shop__sidebar__price">
                                                <form action="{{ route('shop') }}" method="GET" id="priceFilter">
                                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                                    <input type="hidden" name="category" value="{{ request('category') }}">
                                                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" class="form-control" min="0" step="0.01">
                                                        </div>
                                                        <div class="col-6">
                                                            <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" class="form-control" min="0" step="0.01">
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm mt-2">Filter</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="shop__product__option">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="shop__product__option__left">
                                    @isset($products)
                                    <p>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} results</p>
                                    @endisset
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <div class="shop__product__option__right">
                                    <p>Sort by:</p>
                                    <form action="{{ route('shop') }}" method="GET" id="sortForm">
                                        <input type="hidden" name="q" value="{{ request('q') }}">
                                        <input type="hidden" name="category" value="{{ request('category') }}">
                                        <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                                        <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                                        <select name="sort" onchange="document.getElementById('sortForm').submit()">
                                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                            <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z to A</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        @isset($products)
                            @forelse($products as $p)
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <div class="product__item">
                                    <div class="product__item__pic set-bg" data-setbg="{{ $p->image ? asset('storage/'.$p->image) : asset('img/product/product-1.jpg') }}">
                                        <ul class="product__hover">
                                            <li><a href="#"><img src="{{ asset('img/icon/heart.png') }}" alt=""></a></li>
                                            <li><a href="#"><img src="{{ asset('img/icon/compare.png') }}" alt=""> <span>Compare</span></a></li>
                                            <li><a href="{{ route('shop.details', $p->id) }}"><img src="{{ asset('img/icon/search.png') }}" alt=""></a></li>
                                        </ul>
                                    </div>
                                    <div class="product__item__text">
                                        <h6>{{ $p->title }}</h6>
                                        <a href="{{ route('shop.details', $p->id) }}" class="add-cart">View Details</a>
                                        <div class="rating">
                                            <i class="fa fa-star-o"></i>
                                            <i class="fa fa-star-o"></i>
                                            <i class="fa fa-star-o"></i>
                                            <i class="fa fa-star-o"></i>
                                            <i class="fa fa-star-o"></i>
                                        </div>
                                        <h5>${{ number_format((float)$p->price, 2) }}</h5>
                                        <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $p->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-dark mt-2 add-to-cart-btn">+ Add To Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <h4>No products found</h4>
                                    <p>Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('shop') }}" class="btn btn-primary">View All Products</a>
                                </div>
                            </div>
                            @endforelse
                        @endisset
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            @isset($products)
                                {{ $products->withQueryString()->links() }}
                            @endisset
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shop Section End -->

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

    <!-- Js Plugins -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/mixitup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    
    @include('components.cart-script')
</body>

</html>