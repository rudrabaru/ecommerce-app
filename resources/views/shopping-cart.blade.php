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
                        <form id="discount-form">
                            @csrf
                            <input type="text" name="discount_code" id="discount_code" placeholder="Coupon code" value="{{ session('cart_discount_code', '') }}">
                            <button type="submit" id="apply-discount">Apply</button>
                        </form>
                        @if(session('cart_discount_code') || (isset($discountAmount) && $discountAmount > 0))
                            <div class="mt-2">
                                <small class="text-success">
                                    Applied: {{ session('cart_discount_code') ?? 'BARU20' }} 
                                    <a href="#" id="remove-discount" class="text-danger">Remove</a>
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="cart__total">
                        <h6>Cart total</h6>
                        <ul>
                            <li>Subtotal <span>${{ number_format(($subtotal ?? 0), 2) }}</span></li>
                            @if(($discountAmount ?? 0) > 0)
                                <li class="text-success">Discount <span>-${{ number_format($discountAmount, 2) }}</span></li>
                            @endif
                            <li>Total <span>${{ number_format(($total ?? $subtotal ?? 0), 2) }}</span></li>
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
                        updateCartCount(response.cart_count);
                        location.reload();
                    },
                    error: function() {
                        Swal.fire('Error', 'Error updating quantity', 'error');
                    }
                });
            });
            
            // Remove item
            $('.remove-item').on('click', function() {
                const productId = $(this).data('product-id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/cart/' + productId,
                            method: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                updateCartCount(response.cart_count);
                                Swal.fire('Removed!', 'Item has been removed from cart.', 'success');
                                location.reload();
                            },
                            error: function() {
                                Swal.fire('Error', 'Error removing item', 'error');
                            }
                        });
                    }
                });
            });
            
            // Clear cart
            $('#clear-cart').on('click', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will clear all items from your cart!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, clear cart!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/cart',
                            method: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                updateCartCount(response.cart_count);
                                Swal.fire('Cleared!', 'Your cart has been cleared.', 'success');
                                location.reload();
                            },
                            error: function() {
                                Swal.fire('Error', 'Error clearing cart', 'error');
                            }
                        });
                    }
                });
            });

            // Apply discount code
            $('#discount-form').on('submit', function(e) {
                e.preventDefault();
                const discountCode = $('#discount_code').val().trim();
                
                if (!discountCode) {
                    Swal.fire('Error', 'Please enter a discount code', 'error');
                    return;
                }

                $.ajax({
                    url: '/cart/discount/apply',
                    method: 'POST',
                    data: {
                        discount_code: discountCode,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Success', response.message, 'success');
                        location.reload();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error', response.message || 'Invalid discount code', 'error');
                    }
                });
            });

            // Remove discount code
            $('#remove-discount').on('click', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '/cart/discount',
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Success', response.message, 'success');
                        location.reload();
                    },
                    error: function() {
                        Swal.fire('Error', 'Error removing discount code', 'error');
                    }
                });
            });

            // Function to update cart count in navbar
            function updateCartCount(count) {
                $('#cart-count').text(count);
            }
        });
    </script>
</body>

</html>