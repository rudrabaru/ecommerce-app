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
                                            <img src="{{ $item['image_url'] ?? ($item['image'] ? asset('storage/'.$item['image']) : asset('img/shopping-cart/cart-1.jpg')) }}" alt="">
                                        </div>
                                        <div class="product__cart__item__text">
                                            <h6>{{ $item['name'] }}</h6>
                                            <h5>${{ number_format($item['price'], 2) }}</h5>
                                        </div>
                                    </td>
                                    <td class="quantity__item">
                                        <div class="quantity">
                                            <div class="pro-qty-2">
                                                <button type="button" class="qtybtn dec">-</button>
                                                <input type="number" value="{{ $item['quantity'] }}" min="1" class="quantity-input" data-product-id="{{ $item['product_id'] }}" readonly>
                                                <button type="button" class="qtybtn inc">+</button>
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
                            <input type="text" name="discount_code" id="discount_code" placeholder="Coupon code" value="{{ $discountCode }}">
                            <button type="submit" id="apply-discount">Apply</button>
                        </form>
                        <div class="mt-2 applied-discount" style="{{ (($discountAmount ?? 0) > 0) ? '' : 'display: none;' }}">
                            <small class="text-success">
                                <span class="applied-label">Applied</span>: <span class="applied-code">{{ $discountCode }}</span>
                                <span class="applied-affects" style="margin-left:6px;"></span>
                                <a href="#" id="remove-discount" class="text-danger" style="margin-left:8px;">Remove</a>
                            </small>
                        </div>
                    </div>
                    <div class="cart__total">
                        <h6>Cart total</h6>
                        <ul>
                            <li>Subtotal <span class="subtotal-amount">${{ number_format(($subtotal ?? 0), 2) }}</span></li>
                            <li class="text-success discount-row" style="{{ (($discountAmount ?? 0) > 0) ? '' : 'display: none;' }}">
                                Discount <span class="discount-amount">-${{ number_format($discountAmount ?? 0, 2) }}</span>
                            </li>
                            <li>Total <span class="total-amount">${{ number_format(($total ?? $subtotal ?? 0), 2) }}</span></li>
                        </ul>
                        <a href="{{ route('checkout') }}" class="primary-btn">Proceed to checkout</a>
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
    
    <style>
        /* Custom quantity selector styles */
        .pro-qty, .pro-qty-2 {
            display: flex;
            align-items: center;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            width: 120px;
        }
        
        .pro-qty input, .pro-qty-2 input {
            border: none !important;
            text-align: center;
            flex: 1;
            padding: 8px 0;
            background: white;
            font-weight: 600;
        }
        
        .pro-qty .qtybtn, .pro-qty-2 .qtybtn {
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
        
        .pro-qty .qtybtn:hover, .pro-qty-2 .qtybtn:hover {
            background: #e7ab3c;
            color: white;
        }
        
        .pro-qty .qtybtn.dec, .pro-qty-2 .qtybtn.dec {
            border-right: 1px solid #e5e5e5;
        }
        
        .pro-qty .qtybtn.inc, .pro-qty-2 .qtybtn.inc {
            border-left: 1px solid #e5e5e5;
        }
    </style>
    
    <script>
        // Defer binding until jQuery is available to avoid "$ is not defined"
        (function waitForjQuery(){
            if (!window.jQuery || !$.fn) { return setTimeout(waitForjQuery, 50); }

        // Helper function to update cart item price in real-time
        function updateCartItemPrice($row, quantity) {
            const priceText = $row.find('.product__cart__item__text h5').text().replace('$', '').replace(',', '');
            const price = parseFloat(priceText);
            const total = price * quantity;
            $row.find('.cart__price').text('$' + total.toFixed(2));
        }
        
        // Helper function to update cart quantity via AJAX
        function updateCartQuantity(productId, quantity) {
            $.ajax({
                url: '/cart/' + productId,
                method: 'PATCH',
                data: {
                    quantity: quantity,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    updateCartCount(response.cart_count);
                    if (typeof response.discount_amount !== 'undefined') {
                        updateDiscountDisplay(parseFloat(response.discount_amount) || 0);
                    } else {
                        updateCartTotals();
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error updating quantity', 'error');
                }
            });
        }
        
        // Helper function to update cart totals
        function updateCartTotals() {
            let subtotal = 0;
            $('tbody tr').each(function() {
                const priceText = $(this).find('.product__cart__item__text h5').text().replace('$', '').replace(',', '');
                const quantity = parseInt($(this).find('.quantity-input').val());
                const price = parseFloat(priceText);
                if (!isNaN(price) && !isNaN(quantity)) {
                    subtotal += price * quantity;
                }
            });
            
            let discountAmount = 0;
            if ($('.discount-row').is(':visible')) {
                const discountText = $('.discount-amount').text().replace('-$', '').replace('$', '').replace(',', '');
                discountAmount = parseFloat(discountText) || 0;
            }
            
            const total = subtotal - discountAmount;
            
            $('.subtotal-amount').text('$' + subtotal.toFixed(2));
            $('.total-amount').text('$' + total.toFixed(2));
        }

        $(document).ready(function() {
            // Update quantity on direct input change
            $('.quantity-input').on('change', function() {
                const productId = $(this).data('product-id');
                const quantity = $(this).val();
                const $row = $(this).closest('tr');
                
                updateCartItemPrice($row, quantity);
                // Immediately reflect totals in UI; server response will refine discount
                updateCartTotals();
                updateCartQuantity(productId, quantity);
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
                                // Remove the item from the table without page reload
                                $('tr[data-product-id="' + productId + '"]').fadeOut(300, function() {
                                    $(this).remove();
                                    if (typeof response.discount_amount !== 'undefined') {
                                        updateDiscountDisplay(parseFloat(response.discount_amount) || 0);
                                    } else {
                                        updateCartTotals();
                                    }
                                });
                            },
                            error: function(xhr) {
                                const response = xhr.responseJSON;
                                Swal.fire('Error', response.message || 'Error removing item', 'error');
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
                                // Clear the cart table without page reload
                                $('tbody tr').fadeOut(300, function() {
                                    $(this).remove();
                                    updateCartTotals();
                                });
                            },
                            error: function(xhr) {
                                const response = xhr.responseJSON;
                                Swal.fire('Error', response.message || 'Error clearing cart', 'error');
                            }
                        });
                    }
                });
            });

            // Apply discount code
            $('#discount-form').on('submit', function(e) {
                e.preventDefault();
                const discountCode = $('#discount_code').val().trim().toUpperCase();
                
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
                        // Update discount display without page reload
                        updateDiscountDisplay(response.discount_amount || 0);
                        // Update discount code input
                        if (response.discount_code) {
                            $('#discount_code').val(response.discount_code);
                        }
                        // Show affected items info if provided and > 0
                        if (typeof response.affected_items !== 'undefined') {
                            const n = parseInt(response.affected_items, 10) || 0;
                            if (n > 0) {
                                $('.applied-discount .applied-affects').text('(applies to ' + n + ' item' + (n>1?'s':'') + ')');
                            } else {
                                $('.applied-discount .applied-affects').text('');
                            }
                        }
                    },
                    error: function(xhr) {
                        try {
                            const response = xhr.responseJSON || JSON.parse(xhr.responseText);
                            console.error('Discount apply error response:', response);
                            Swal.fire('Error', (response && response.message) || 'Invalid discount code', 'error');
                        } catch(e) {
                            console.error('Discount apply error raw:', xhr.responseText);
                            Swal.fire('Error', 'Invalid discount code', 'error');
                        }
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
                        // Update discount display without page reload
                        updateDiscountDisplay(0);
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error', response.message || 'Error removing discount code', 'error');
                    }
                });
            });

            // Function to update cart count in navbar
            function updateCartCount(count) {
                $('#cart-count').text(count);
            }

            // Function to update cart totals dynamically (single definition on this page)
            function updateCartTotals() {
                let subtotal = 0;
                $('tbody tr').each(function() {
                    const priceText = $(this).find('.product__cart__item__text h5').text().replace('$', '').replace(',', '');
                    const quantity = parseInt($(this).find('.quantity-input').val());
                    const price = parseFloat(priceText);
                    if (!isNaN(price) && !isNaN(quantity)) {
                        subtotal += price * quantity;
                    }
                });
                let discountAmount = 0;
                if ($('.discount-row').is(':visible')) {
                    const discountText = $('.discount-amount').text().replace('-$', '').replace('$', '').replace(',', '');
                    discountAmount = parseFloat(discountText) || 0;
                }
                const total = subtotal - discountAmount;
                $('.subtotal-amount').text('$' + subtotal.toFixed(2));
                $('.total-amount').text('$' + total.toFixed(2));
            }

            // Function to update discount display
            function updateDiscountDisplay(discountAmount) {
                
                
                if (discountAmount > 0) {
                    $('.discount-amount').text('-$' + discountAmount.toFixed(2));
                    $('.discount-row').show();
                    // Keep user-entered code in the input, do not force static code
                    $('.applied-discount').show();
                } else {
                    $('.discount-amount').text('$0.00');
                    $('.discount-row').hide();
                    $('#discount_code').val('');
                    $('.applied-discount').hide();
                    $('.applied-discount .applied-affects').text('');
                }
                
                // Update totals after discount change
                updateCartTotals();
            }
        });

        })();
    </script>

 </body>

</html>