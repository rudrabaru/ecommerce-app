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
    // Global, safe, idempotent initializer for checkout address modal
    (function() {
        var INIT_KEY = '__address_modal_initialized__';
        // Toggleable debug flag (set to true to see detailed traces)
        window.ADDRESS_DEBUG = window.ADDRESS_DEBUG !== undefined ? window.ADDRESS_DEBUG : true;

        function dbgLevel() { return window.ADDRESS_DEBUG; }
        function dbg() { if (!dbgLevel()) return; try { console.log.apply(console, formatArgs(arguments)); } catch(e) {} }
        function dbgWarn() { if (!dbgLevel()) return; try { console.warn.apply(console, formatArgs(arguments)); } catch(e) {} }
        function dbgError() { if (!dbgLevel()) return; try { console.error.apply(console, formatArgs(arguments)); } catch(e) {} }
        function formatArgs(args) {
            var a = Array.prototype.slice.call(args);
            a.unshift('[AddressModal]');
            return a;
        }

        // Expose a global open function immediately (will initialize lazily if needed)
        window.openAddressModal = function(id) {
            dbg('open requested. id=', id);
            if (!window[INIT_KEY]) {
                dbgWarn('Not initialized yet. Initializing now...');
                safeInitialize();
            }
            if (typeof jQuery === 'undefined') {
                dbgError('jQuery not available; cannot open modal yet');
                return;
            }
            if (!document.getElementById('addressModal')) {
                dbgError('#addressModal element not found in DOM');
                return;
            }
            console.groupCollapsed && console.groupCollapsed('[AddressModal] Open flow');
            resetAddressForm();
            if (id) {
                $('#addressModalLabel').text('Edit Address');
                $('#addressMethod').val('PUT');
                $('#addressId').val(id);
                dbg('Fetching address for edit', { id: id });
                $.ajax({
                    url: '/addresses/' + id + '/edit',
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(response) {
                    dbg('Edit fetch response received', response);
                    if (response && response.success) {
                        fillAddressForm(response.address || {});
                    } else {
                        dbgError('Unexpected edit response payload', response);
                    }
                }).fail(function(xhr) {
                    dbgError('Failed to fetch address for edit', xhr.status, xhr.responseText);
                    alert('Failed to load address data');
                }).always(function() {
                    $('#addressModal').modal('show');
                    console.groupEnd && console.groupEnd();
                });
            } else {
                $('#addressModalLabel').text('Add New Address');
                $('#addressMethod').val('POST');
                $('#addressId').val('');
                $('#addressModal').modal('show');
                console.groupEnd && console.groupEnd();
            }
        };

        // Provide location data globally so it can be reused anywhere
        window.ADDRESS_LOCATION_DATA = window.ADDRESS_LOCATION_DATA || {
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

        function safeInitialize() {
            if (window[INIT_KEY]) return; // already initialized
            if (typeof jQuery === 'undefined') {
                dbgWarn('Waiting for jQuery to load...');
                // Retry shortly until jQuery is present
                setTimeout(safeInitialize, 50);
                return;
            }

            $(function() {
                if (window[INIT_KEY]) return;
                dbg('Initializing bindings');
                bindDropdownHandlers();
                bindValidationHandlers();
                bindModalHandlers();
                bindSaveHandler();
                window[INIT_KEY] = true;
            });
        }

        function bindDropdownHandlers() {
            dbg('Binding dropdown handlers');
            // Country change
            $(document).on('change', '#country', function() {
                console.groupCollapsed && console.groupCollapsed('[AddressModal] Country change');
                var country = $(this).val();
                var stateSelect = $('#state');
                var citySelect = $('#city');
                dbg('Country changed to:', country, 'stateSelect exists:', !!stateSelect.length, 'citySelect exists:', !!citySelect.length);
                if (!stateSelect.length || !citySelect.length) {
                    dbgError('State/City selects not found');
                    console.groupEnd && console.groupEnd();
                    return;
                }
                stateSelect.html('<option value="">Select State</option>');
                citySelect.html('<option value="">Select City</option>');

                var data = window.ADDRESS_LOCATION_DATA || {};
                if (!country || !data[country]) {
                    dbgWarn('No state data for country:', country, 'Using data keys:', Object.keys(data || {}));
                    validateAddressForm();
                    console.groupEnd && console.groupEnd();
                    return;
                }
                var states = Object.keys((data[country] && data[country].states) || {});
                dbg('Resolved states for', country, 'count:', states.length);
                if (!states.length) { dbgWarn('States list empty for country:', country); }
                states.forEach(function(s) { stateSelect.append('<option value="' + s + '">' + s + '</option>'); });
                dbg('States appended');
                validateAddressForm();
                console.groupEnd && console.groupEnd();
            });

            // State change
            $(document).on('change', '#state', function() {
                console.groupCollapsed && console.groupCollapsed('[AddressModal] State change');
                var country = $('#country').val();
                var state = $(this).val();
                dbg('State changed to:', state, 'under country:', country);
                var citySelect = $('#city');
                if (!citySelect.length) {
                    dbgError('City select not found');
                    console.groupEnd && console.groupEnd();
                    return;
                }
                citySelect.html('<option value="">Select City</option>');

                var data = window.ADDRESS_LOCATION_DATA || {};
                if (!country || !state || !data[country] || !data[country].states[state]) {
                    dbgWarn('No city data for', country, state);
                    validateAddressForm();
                    console.groupEnd && console.groupEnd();
                    return;
                }
                (data[country].states[state] || []).forEach(function(city) {
                    citySelect.append('<option value="' + city + '">' + city + '</option>');
                });
                dbg('Cities appended for', state, 'count:', (data[country].states[state] || []).length);
                validateAddressForm();
                console.groupEnd && console.groupEnd();
            });

            // City change
            $(document).on('change', '#city', function() {
                dbg('City changed to:', $(this).val());
            });

            // If Nice Select is enhancing selects, proxy its option clicks back to the native select change
            $(document).on('click', '#addressModal .nice-select .option', function() {
                var select = $(this).closest('.nice-select').prev('select');
                if (select && select.length) {
                    dbg('Proxying nice-select change for', select.attr('id'));
                    setTimeout(function(){ select.trigger('change'); }, 0);
                }
            });
        }

        function bindValidationHandlers() {
            // Phone formatting and validation
            $(document).on('input', '#phone', function() {
                var value = String($(this).val() || '').replace(/\D/g, '');
                var code = $('#country_code').val();
                if (code === '+91' && value.length > 10) value = value.substring(0, 10);
                if (code === '+1' && value.length > 10) value = value.substring(0, 10);
                $(this).val(value);
                validateAddressForm();
            });
            $(document).on('change', '#country_code', function() { validateAddressForm(); });
            $(document).on('input change', '#addressForm input, #addressForm select', function() { validateAddressForm(); });
        }

        function bindModalHandlers() {
            $('#addressModal').on('shown.bs.modal', function() {
                dbg('Modal shown. Current selections:', {
                    country: $('#country').val(),
                    state: $('#state').val(),
                    city: $('#city').val()
                });
                validateAddressForm();
            });
            $('#addressModal').on('hidden.bs.modal', function() {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
            });
        }

        function bindSaveHandler() {
            window.saveAddress = function() {
                var form = document.getElementById('addressForm');
                if (!form) {
                    dbgError('#addressForm not found');
                    return;
                }
                var id = $('#addressId').val();
                var method = $('#addressMethod').val();
                var url = id ? '/addresses/' + id : '/addresses';
                var formData = new FormData(form);
                if (id) formData.append('_method', method);
                $('#addressSpinner').removeClass('d-none');
                $('#addressSaveBtn').prop('disabled', true);
                dbg('Submitting address', { url: url, method: id ? method : 'POST' });
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(response) {
                    dbg('Save response', response);
                    if (response && response.success) {
                        $('#addressModal').modal('hide');
                        location.reload();
                    } else {
                        dbgError('Save response did not indicate success', response);
                        alert('Failed to save address.');
                    }
                }).fail(function(xhr) {
                    var response = xhr.responseJSON;
                    if (response && response.errors) {
                        Object.keys(response.errors).forEach(function(key) {
                            var input = $('input[name="' + key + '"], select[name="' + key + '"]');
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(response.errors[key][0]);
                        });
                    } else {
                        dbgError('Save failed', xhr.status, xhr.responseText);
                        alert('An error occurred. Please try again.');
                    }
                }).always(function() {
                    $('#addressSpinner').addClass('d-none');
                    validateAddressForm();
                });
            };
        }

        function fillAddressForm(address) {
            $('#first_name').val(address.first_name || '');
            $('#last_name').val(address.last_name || '');
            $('#company').val(address.company || '');
            $('#address_line_1').val(address.address_line_1 || '');
            $('#address_line_2').val(address.address_line_2 || '');
            $('#postal_code').val(address.postal_code || '');
            $('#phone').val(address.phone || '');
            $('#is_default').prop('checked', !!address.is_default);

            // Set country/state/city in order to repopulate dependent dropdowns
            var country = address.country || '';
            var state = address.state || '';
            var city = address.city || '';
            dbg('fillAddressForm applying values', { country: country, state: state, city: city });
            $('#country').val(country).trigger('change');
            // After states populated, set state and trigger change to populate cities
            if (state) setTimeout(function(){ $('#state').val(state).trigger('change'); }, 0);
            if (city) setTimeout(function(){ $('#city').val(city); }, 0);

            validateAddressForm();
        }

        function resetAddressForm() {
            var form = document.getElementById('addressForm');
            if (form && form.reset) form.reset();
            $('#addressMethod').val('POST');
            $('#addressId').val('');
            $('#addressSpinner').addClass('d-none');
            $('.invalid-feedback').text('');
            $('.form-control').removeClass('is-invalid');
            validateAddressForm();
        }

        function validateAddressForm() {
            var firstName = ($('#first_name').val() || '').trim();
            var lastName = ($('#last_name').val() || '').trim();
            var phone = ($('#phone').val() || '').trim();
            var country = $('#country').val();
            var state = $('#state').val();
            var city = $('#city').val();
            var postalCode = ($('#postal_code').val() || '').trim();
            var addressLine1 = ($('#address_line_1').val() || '').trim();

            var isValid = !!(firstName && lastName && phone && country && state && city && postalCode && addressLine1);
            $('#addressSaveBtn').prop('disabled', !isValid);
        }

        // Kick off initialization
        safeInitialize();
    })();
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