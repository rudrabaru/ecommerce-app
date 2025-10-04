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
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddressModal()">
                                            <i class="fa fa-plus"></i> Add New Address
                                        </button>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <p>No addresses found. Please add a shipping address first.</p>
                                        <button type="button" class="btn btn-primary" onclick="openAddressModal()">
                                            Add Address
                                        </button>
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
                                $user = Auth::user();
                                $subtotal = 0;
                                $discountAmount = 0;
                                
                                if ($user) {
                                    // For logged-in users, get cart from database
                                    $cart = \App\Models\Cart::where('user_id', $user->id)->first();
                                    if ($cart) {
                                        $cartItems = $cart->items()->with('product')->get();
                                        $discountAmount = (float) ($cart->discount_amount ?? 0);
                                    } else {
                                        $cartItems = collect();
                                    }
                                } else {
                                    // For guests, get cart from session
                                    $sessionCart = session('cart', []);
                                    $cartItems = collect($sessionCart);
                                    $discountAmount = (float) session('cart_discount', 0);
                                }
                            @endphp
                            @foreach($cartItems as $item)
                                @php
                                    if ($user) {
                                        // Database cart item
                                        $product = $item->product;
                                        $itemPrice = $item->unit_price;
                                        $itemQuantity = $item->quantity;
                                    } else {
                                        // Session cart item
                                        $product = \Modules\Products\Models\Product::find($item['product_id']);
                                        $itemPrice = $item['price'];
                                        $itemQuantity = $item['quantity'];
                                    }
                                    
                                    if ($product) {
                                        $subtotal += $itemPrice * $itemQuantity;
                                    }
                                @endphp
                                @if($product)
                                    <li>{{ $product->title }} x{{ $itemQuantity }} 
                                        <span>${{ number_format($itemPrice * $itemQuantity, 2) }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <div class="checkout__order__subtotal">Subtotal <span>${{ number_format($subtotal, 2) }}</span></div>
                        @if($discountAmount > 0)
                            <div class="checkout__order__discount">Discount <span>-${{ number_format($discountAmount, 2) }}</span></div>
                        @endif
                        <div class="checkout__order__total">Total <span>${{ number_format($subtotal - $discountAmount, 2) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Checkout Section End -->

    <x-footer />

    @include('partials.address-modal')

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
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}" onerror="loadJQueryFromCDN()"></script>
    <script>
        // Test jQuery loading
        console.log('jQuery test - typeof $:', typeof $);
        console.log('jQuery test - typeof jQuery:', typeof jQuery);
        if (typeof $ !== 'undefined') {
            console.log('jQuery version:', $.fn.jquery);
        }
        
        // Fallback function to load jQuery from CDN
        function loadJQueryFromCDN() {
            console.log('Local jQuery failed, loading from CDN...');
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.3.1.min.js';
            script.onload = function() {
                console.log('jQuery loaded from CDN successfully');
            };
            script.onerror = function() {
                console.error('Failed to load jQuery from CDN as well');
            };
            document.head.appendChild(script);
        }
    </script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('js/mixitup.min.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    <!-- Address Modal JavaScript -->
    <script>
    // Make openAddressModal available immediately
    window.openAddressModal = function(id) {
        console.log('openAddressModal called with id:', id);
        // Check if jQuery is available
        if (typeof jQuery === 'undefined') {
            console.error('jQuery not available yet, please wait...');
            return;
        }
        
        // If jQuery is available, proceed with the modal
        if (typeof window.initializeAddressModal === 'function') {
            // Use the existing function
            window.initializeAddressModal();
        } else {
            console.log('Initializing address modal on demand...');
            // Initialize on demand
            initializeAddressModal();
        }
        
        // Show the modal
        $('#addressModal').modal('show');
    };
    
    // Wait for everything to be loaded
    window.addEventListener('load', function() {
        console.log('Page fully loaded, checking jQuery...');
        
        // Function to initialize address modal functionality
        function initializeAddressModal() {
        console.log('Initializing address modal functionality...');
        
        // State and city data
        const locationData = {
            'India': {
                'states': {
                    'Delhi': ['New Delhi', 'Central Delhi', 'East Delhi', 'North Delhi', 'South Delhi', 'West Delhi'],
                    'Maharashtra': ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad', 'Solapur'],
                    'Karnataka': ['Bangalore', 'Mysore', 'Hubli', 'Mangalore', 'Belgaum', 'Gulbarga'],
                    'Tamil Nadu': ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli'],
                    'Gujarat': ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar']
                }
            },
            'United States': {
                'states': {
                    'California': ['Los Angeles', 'San Francisco', 'San Diego', 'San Jose', 'Fresno', 'Sacramento'],
                    'New York': ['New York City', 'Buffalo', 'Rochester', 'Yonkers', 'Syracuse', 'Albany'],
                    'Texas': ['Houston', 'San Antonio', 'Dallas', 'Austin', 'Fort Worth', 'El Paso'],
                    'Florida': ['Miami', 'Tampa', 'Orlando', 'Jacksonville', 'St. Petersburg', 'Hialeah']
                }
            },
            'United Kingdom': {
                'states': {
                    'England': ['London', 'Birmingham', 'Manchester', 'Liverpool', 'Leeds', 'Sheffield'],
                    'Scotland': ['Edinburgh', 'Glasgow', 'Aberdeen', 'Dundee', 'Stirling', 'Perth'],
                    'Wales': ['Cardiff', 'Swansea', 'Newport', 'Wrexham', 'Barry', 'Caerphilly']
                }
            }
        };

        // Country change handler
        $(document).on('change', '#country', function() {
            const country = $(this).val();
            const stateSelect = $('#state');
            const citySelect = $('#city');
            
            // Clear state and city options
            stateSelect.html('<option value="">Select State</option>');
            citySelect.html('<option value="">Select City</option>');
            
            if (country && locationData[country]) {
                // Populate states
                Object.keys(locationData[country].states).forEach(function(state) {
                    stateSelect.append(`<option value="${state}">${state}</option>`);
                });
            }
            
            validateAddressForm();
        });

        // State change handler
        $(document).on('change', '#state', function() {
            const country = $('#country').val();
            const state = $(this).val();
            const citySelect = $('#city');
            
            // Clear city options
            citySelect.html('<option value="">Select City</option>');
            
            if (country && state && locationData[country] && locationData[country].states[state]) {
                // Populate cities
                locationData[country].states[state].forEach(function(city) {
                    citySelect.append(`<option value="${city}">${city}</option>`);
                });
            }
            
            validateAddressForm();
        });

        // City change handler
        $(document).on('change', '#city', function() {
            validateAddressForm();
        });

        // Phone number formatting
        $(document).on('input', '#phone', function() {
            let value = $(this).val().replace(/\D/g, '');
            const countryCode = $('#country_code').val();
            
            // Format based on country code
            if (countryCode === '+91' && value.length > 10) {
                value = value.substring(0, 10);
            } else if (countryCode === '+1' && value.length > 10) {
                value = value.substring(0, 10);
            }
            
            $(this).val(value);
            validateAddressForm();
        });

        // Country code change handler
        $(document).on('change', '#country_code', function() {
            validateAddressForm();
        });

        // Store the function globally for reuse
        window.initializeAddressModal = function() {
            console.log('Setting up address modal functionality...');
            
            // Address modal functionality for checkout page
            window.openAddressModal = function(id) {
                resetAddressForm();
                if (id) {
                    $('#addressModalLabel').text('Edit Address');
                    $('#addressMethod').val('PUT');
                    $('#addressId').val(id);
                    
                    // Fetch address data
                    $.ajax({
                        url: '/addresses/' + id + '/edit',
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                fillAddressForm(response.address);
                                $('#addressModal').modal('show');
                            }
                        },
                        error: function() {
                            alert('Failed to load address data');
                        }
                    });
                } else {
                    $('#addressModalLabel').text('Add New Address');
                    $('#addressMethod').val('POST');
                    $('#addressId').val('');
                    $('#addressModal').modal('show');
                }
            };
        };

        function fillAddressForm(address) {
            $('#first_name').val(address.first_name || '');
            $('#last_name').val(address.last_name || '');
            $('#company').val(address.company || '');
            $('#address_line_1').val(address.address_line_1 || '');
            $('#address_line_2').val(address.address_line_2 || '');
            $('#city').val(address.city || '');
            $('#state').val(address.state || '');
            $('#postal_code').val(address.postal_code || '');
            $('#country').val(address.country || '');
            $('#phone').val(address.phone || '');
            $('#is_default').prop('checked', address.is_default || false);
            
            validateAddressForm();
        }

        function resetAddressForm() {
            $('#addressForm')[0].reset();
            $('#addressMethod').val('POST');
            $('#addressId').val('');
            $('#addressSpinner').addClass('d-none');
            $('.invalid-feedback').text('');
            $('.form-control').removeClass('is-invalid');
            validateAddressForm();
        }

        function validateAddressForm() {
            const firstName = $('#first_name').val().trim();
            const lastName = $('#last_name').val().trim();
            const phone = $('#phone').val().trim();
            const country = $('#country').val();
            const state = $('#state').val();
            const city = $('#city').val();
            const postalCode = $('#postal_code').val().trim();
            const addressLine1 = $('#address_line_1').val().trim();

            let isValid = true;

            if (!firstName) isValid = false;
            if (!lastName) isValid = false;
            if (!phone) isValid = false;
            if (!country) isValid = false;
            if (!state) isValid = false;
            if (!city) isValid = false;
            if (!postalCode) isValid = false;
            if (!addressLine1) isValid = false;

            $('#addressSaveBtn').prop('disabled', !isValid);
        }

        // Initialize dropdown functionality when modal is shown
        $('#addressModal').on('shown.bs.modal', function() {
            // Trigger validation to set initial state
            validateAddressForm();
        });

        window.saveAddress = function() {
            const form = document.getElementById('addressForm');
            const id = $('#addressId').val();
            const method = $('#addressMethod').val();
            const url = id ? '/addresses/' + id : '/addresses';

            const formData = new FormData(form);
            if (id) {
                formData.append('_method', method);
            }

            $('#addressSpinner').removeClass('d-none');
            $('#addressSaveBtn').prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#addressModal').modal('hide');
                        // Reload the checkout page to refresh address list
                        location.reload();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        Object.keys(response.errors).forEach(function(key) {
                            const input = $('input[name="' + key + '"], select[name="' + key + '"]');
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(response.errors[key][0]);
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                },
                complete: function() {
                    $('#addressSpinner').addClass('d-none');
                    validateAddressForm();
                }
            });
        };

        // Form validation on input change
        $(document).on('input change', '#addressForm input, #addressForm select', function() {
            validateAddressForm();
        });

        // Clear validation errors when modal is closed
        $('#addressModal').on('hidden.bs.modal', function() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        });
    }

        // Initialize when jQuery is ready
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is available, initializing...');
            $(document).ready(function() {
                console.log('DOM is ready, initializing address modal...');
                window.initializeAddressModal();
            });
        } else {
            console.error('jQuery is not available!');
        }
    });
    </script>

    <style>
    /* Order Summary Alignment Fix */
    .checkout__order__subtotal,
    .checkout__order__discount,
    .checkout__order__total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .checkout__order__discount {
        color: #28a745;
        font-weight: 500;
    }

    .checkout__order__discount span {
        color: #dc3545;
        font-weight: 600;
    }

    .checkout__order__total {
        font-weight: 700;
        font-size: 18px;
        color: #e7ab3c;
        border-bottom: 2px solid #e7ab3c;
        margin-top: 10px;
    }

    .checkout__order__total span {
        color: #e7ab3c;
    }

    /* Address Modal Dropdown Fix */
    .form-control {
        appearance: auto !important;
        -webkit-appearance: menulist !important;
        -moz-appearance: menulist !important;
    }

    .form-control:focus {
        border-color: #e7ab3c !important;
        box-shadow: 0 0 0 0.2rem rgba(231, 171, 60, 0.25) !important;
        outline: none !important;
    }
    </style>
</body>

</html>