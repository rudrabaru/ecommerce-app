<x-header />

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Shopping Cart</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}">Home</a>
                            <a href="{{ route('shop') }}">Shop</a>
                            <span>Shopping Cart</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Shopping Cart Section Begin -->
    <section class="shopping-cart spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="shopping__cart__table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($items ?? []) as $item)
                                <tr data-product-id="{{ $item['product_id'] }}">
                                    <td class="product__cart__item">
                                        <div class="product__cart__item__pic">
                                            <img src="{{ $item['image'] ? asset('storage/'.$item['image']) : asset('img/shopping-cart/cart-1.jpg') }}" alt="">
                                        </div>
                                        <div class="product__cart__item__text">
                                            <h6>{{ $item['name'] }}</h6>
                                            <h5>${{ number_format($item['price'], 2) }}</h5>
                                        </div>
                                    </td>
                                    <td class="quantity__item">
                                        <div class="quantity">
                                            <div class="pro-qty-2">
                                                <input type="number" value="{{ $item['quantity'] }}" min="1" class="quantity-input" data-product-id="{{ $item['product_id'] }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart__price">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                    <td class="cart__close">
                                        <button class="btn btn-sm btn-outline-danger remove-item" data-product-id="{{ $item['product_id'] }}">
                                            <i class="fa fa-close"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <h4>Your cart is empty</h4>
                                        <p>Add some products to get started!</p>
                                        <a href="{{ route('shop') }}" class="btn btn-primary">Continue Shopping</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="continue__btn">
                                <a href="{{ route('shop') }}">Continue Shopping</a>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="continue__btn update__btn">
                                <button class="btn btn-warning" id="clear-cart">
                                    <i class="fa fa-trash"></i> Clear Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cart__discount">
                        <h6>Discount codes</h6>
                        <form action="#">
                            <input type="text" placeholder="Coupon code">
                            <button type="submit">Apply</button>
                        </form>
                    </div>
                    <div class="cart__total">
                        <h6>Cart total</h6>
                        <ul>
                            <li>Subtotal <span>${{ number_format(($subtotal ?? 0), 2) }}</span></li>
                            <li>Total <span>${{ number_format(($subtotal ?? 0), 2) }}</span></li>
                        </ul>
                        <a href="{{ auth()->check() ? route('checkout') : route('login') }}" class="primary-btn">Proceed to checkout</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shopping Cart Section End -->

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
    <script src="{{ asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('js/mixitup.min.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            // Update quantity
            $('.quantity-input').on('change', function() {
                const productId = $(this).data('product-id');
                const quantity = $(this).val();
                
                $.ajax({
                    url: '/cart/' + productId,
                    method: 'PATCH',
                    data: {
                        quantity: quantity,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('Error updating quantity');
                    }
                });
            });
            
            // Remove item
            $('.remove-item').on('click', function() {
                const productId = $(this).data('product-id');
                
                if (confirm('Are you sure you want to remove this item?')) {
                    $.ajax({
                        url: '/cart/' + productId,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Error removing item');
                        }
                    });
                }
            });
            
            // Clear cart
            $('#clear-cart').on('click', function() {
                if (confirm('Are you sure you want to clear your cart?')) {
                    $.ajax({
                        url: '/cart',
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Error clearing cart');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>