<x-header />

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Check Out</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}">Home</a>
                            <a href="{{ route('shop') }}">Shop</a>
                            <span>Check Out</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Checkout Section Begin -->
    <section class="checkout spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="checkout__form">
                        <h4>Billing Details</h4>
                        <form method="post" action="{{ route('checkout.store') }}">
                            @csrf
                            
                            <!-- Address Selection -->
                            <div class="checkout__input">
                                <p>Shipping Address<span>*</span></p>
                                @if($addresses->count() > 0)
                                    <div class="address-selection">
                                        @foreach($addresses as $address)
                                            <div class="address-option">
                                                <input type="radio" name="shipping_address_id" value="{{ $address->id }}" 
                                                       id="address_{{ $address->id }}" 
                                                       {{ $address->is_default ? 'checked' : '' }}>
                                                <label for="address_{{ $address->id }}" class="address-label">
                                                    <div class="address-info">
                                                        <strong>{{ $address->full_name }}</strong>
                                                        @if($address->company)
                                                            <br>{{ $address->company }}
                                                        @endif
                                                        <br>{{ $address->full_address }}
                                                        <br>Phone: {{ $address->phone }}
                                                        @if($address->is_default)
                                                            <span class="default-badge">Default</span>
                                                        @endif
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('addresses.create') }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Address
                                        </a>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>No addresses found. Please add a shipping address first.</p>
                                        <a href="{{ route('addresses.create') }}" class="btn btn-primary">
                                            Add Address
                                        </a>
                                    </div>
                                @endif
                                @error('shipping_address_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="checkout__input">
                                <p>Payment Method<span>*</span></p>
                                <div class="payment-methods">
                                    @foreach($paymentMethods as $method)
                                        <div class="payment-option">
                                            <input type="radio" name="payment_method_id" value="{{ $method->id }}" 
                                                   id="payment_{{ $method->id }}" 
                                                   {{ $loop->first ? 'checked' : '' }}>
                                            <label for="payment_{{ $method->id }}" class="payment-label">
                                                <div class="payment-info">
                                                    <strong>{{ $method->display_name }}</strong>
                                                    @if($method->description)
                                                        <br><small class="text-muted">{{ $method->description }}</small>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('payment_method_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Order Notes -->
                            <div class="checkout__input">
                                <p>Order Notes</p>
                                <textarea name="notes" placeholder="Notes about your order (optional)" 
                                          class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="site-btn" 
                                    {{ $addresses->count() == 0 ? 'disabled' : '' }}>
                                PLACE ORDER
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="checkout__order">
                        <h4>Your Order</h4>
                        <div class="checkout__order__products">Products <span>Total</span></div>
                        <ul>
                            @php
                                $cart = session('cart', []);
                                $subtotal = 0;
                            @endphp
                            @foreach($cart as $item)
                                @php
                                    $product = \Modules\Products\Models\Product::find($item['product_id']);
                                    if ($product) {
                                        $subtotal += $product->price * $item['quantity'];
                                    }
                                @endphp
                                @if($product)
                                    <li>{{ $product->title }} x{{ $item['quantity'] }} 
                                        <span>${{ number_format($product->price * $item['quantity'], 2) }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <div class="checkout__order__subtotal">Subtotal <span>${{ number_format($subtotal, 2) }}</span></div>
                        <div class="checkout__order__total">Total <span>${{ number_format($subtotal, 2) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Checkout Section End -->

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
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/mixitup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>