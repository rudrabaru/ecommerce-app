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
                <!-- Left Column: Billing Details -->
                <div class="col-lg-7">
            <div class="checkout__form">
                        <h4 class="mb-4">Billing Details</h4>
                <form method="post" action="{{ route('checkout.store') }}">
                    @csrf
                            
                            <!-- Address Selection -->
                            <div class="checkout__input">
                                <p class="form-label">Shipping Address <span class="text-danger">*</span></p>
                                @if($addresses->count() > 0)
                                    <div class="address-selection">
                                        @if($addresses->count() == 1)
                                            <!-- Single address -->
                                        @foreach($addresses as $address)
                                                <div class="address-card">
                                                <input type="radio" name="shipping_address_id" value="{{ $address->id }}" 
                                                       id="address_{{ $address->id }}" 
                                                       checked>
                                                <label for="address_{{ $address->id }}" class="address-card-label">
                                                        <div class="address-card-content">
                                                            <div class="address-header">
                                                                <strong class="address-name">{{ $address->full_name }}</strong>
                                                                @if($address->is_default)
                                                                    <span class="badge-default">Default</span>
                                                                @endif
                                                            <div class="address-actions">
                                                                <button type="button" class="icon-btn btn-edit-address" title="Edit" data-id="{{ $address->id }}"><i class="fa fa-pencil"></i></button>
                                                                <button type="button" class="icon-btn btn-delete-address" title="Delete" data-id="{{ $address->id }}"><i class="fa fa-trash"></i></button>
                                                            </div>
                                                            </div>
                                                        @if($address->company)
                                                                <div class="address-company">{{ $address->company }}</div>
                                                        @endif
                                                            <div class="address-text">{{ $address->full_address }}</div>
                                                            <div class="address-phone"><i class="fa fa-phone"></i> {{ $address->phone }}</div>
                                                        </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        @else
                                            <!-- Multiple addresses -->
                                            @php
                                                $firstAddress = $addresses->first();
                                                $otherAddresses = $addresses->skip(1);
                                            @endphp
                                            
                                            <!-- Primary address -->
                                            <div class="address-card">
                                                <input type="radio" name="shipping_address_id" value="{{ $firstAddress->id }}" 
                                                       id="address_{{ $firstAddress->id }}" 
                                                       checked>
                                                <label for="address_{{ $firstAddress->id }}" class="address-card-label">
                                                    <div class="address-card-content">
                                                        <div class="address-header">
                                                            <strong class="address-name">{{ $firstAddress->full_name }}</strong>
                                                            @if($firstAddress->is_default)
                                                                <span class="badge-default">Default</span>
                                                            @endif
                                                            <div class="address-actions">
                                                                <button type="button" class="icon-btn btn-edit-address" title="Edit" data-id="{{ $firstAddress->id }}"><i class="fa fa-pencil"></i></button>
                                                                <button type="button" class="icon-btn btn-delete-address" title="Delete" data-id="{{ $firstAddress->id }}"><i class="fa fa-trash"></i></button>
                                                            </div>
                                                        </div>
                                                        @if($firstAddress->company)
                                                            <div class="address-company">{{ $firstAddress->company }}</div>
                                                        @endif
                                                        <div class="address-text">{{ $firstAddress->full_address }}</div>
                                                        <div class="address-phone"><i class="fa fa-phone"></i> {{ $firstAddress->phone }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <!-- Collapsible other addresses -->
                                            @if($otherAddresses->count() > 0)
                                                <div class="other-addresses-wrapper">
                                                    <button type="button" class="btn-toggle-addresses" 
                                                            data-toggle="collapse" data-target="#otherAddresses" 
                                                            aria-expanded="false" aria-controls="otherAddresses">
                                                        <i class="fa fa-chevron-down"></i> 
                                                        <span>Show {{ $otherAddresses->count() }} more address{{ $otherAddresses->count() > 1 ? 'es' : '' }}</span>
                                                    </button>
                                                    
                                                    <div class="collapse" id="otherAddresses">
                                                        <div class="other-addresses-list">
                                                            @foreach($otherAddresses as $address)
                                                                <div class="address-card">
                                                                    <input type="radio" name="shipping_address_id" value="{{ $address->id }}" 
                                                                           id="address_{{ $address->id }}">
                                                                    <label for="address_{{ $address->id }}" class="address-card-label">
                                                                        <div class="address-card-content">
                                                                            <div class="address-header">
                                                                                <strong class="address-name">{{ $address->full_name }}</strong>
                                                                                <div class="address-actions">
                                                                                    <button type="button" class="icon-btn btn-edit-address" title="Edit" data-id="{{ $address->id }}"><i class="fa fa-pencil"></i></button>
                                                                                    <button type="button" class="icon-btn btn-delete-address" title="Delete" data-id="{{ $address->id }}"><i class="fa fa-trash"></i></button>
                                                                                </div>
                                                                            </div>
                                                                            @if($address->company)
                                                                                <div class="address-company">{{ $address->company }}</div>
                                                                            @endif
                                                                            <div class="address-text">{{ $address->full_address }}</div>
                                                                            <div class="address-phone"><i class="fa fa-phone"></i> {{ $address->phone }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn-add-address" onclick="openAddressModal()">
                                            <i class="fa fa-plus"></i> Add New Address
                                        </button>
                                    </div>
                                @else
                                    <div class="empty-address-state">
                                        <i class="fa fa-map-marker"></i>
                                        <p>No addresses found. Please add a shipping address first.</p>
                                        <button type="button" class="btn-primary-custom" onclick="openAddressModal()">
                                            <i class="fa fa-plus"></i> Add Address
                                        </button>
                                    </div>
                                @endif
                                @error('shipping_address_id')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="checkout__input mt-4">
                                <p class="form-label">Payment Method <span class="text-danger">*</span></p>
                                <div class="payment-methods">
                                    @foreach($paymentMethods as $method)
                                        <div class="payment-card">
                                            <input type="radio" name="payment_method_id" value="{{ $method->id }}" 
                                                   id="payment_{{ $method->id }}" data-method-name="{{ $method->name }}"
                                                   {{ $loop->first ? 'checked' : '' }}>
                                            <label for="payment_{{ $method->id }}" class="payment-card-label">
                                                <div class="payment-card-content">
                                                    <div class="payment-icon">
                                                        @if(strtolower($method->name) === 'cod')
                                                            <i class="fa fa-money"></i>
                                                        @elseif(strtolower($method->name) === 'stripe')
                                                            <i class="fa fa-credit-card"></i>
                                                        @elseif(strtolower($method->name) === 'razorpay')
                                                            <i class="fa fa-credit-card-alt"></i>
                                                        @else
                                                            <i class="fa fa-payment"></i>
                                                        @endif
                                                    </div>
                                                    <div class="payment-details">
                                                        <strong class="payment-name">{{ $method->display_name }}</strong>
                                                    @if($method->description)
                                                            <small class="payment-desc">{{ $method->description }}</small>
                                                    @endif
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('payment_method_id')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Stripe Elements -->
                            <div id="stripe-elements-container" class="stripe-container" style="display:none;">
                    <div class="checkout__input">
                                    <p class="form-label">Card Details <span class="text-danger">*</span></p>
                                    <div id="stripe-card-element" class="stripe-card-input"></div>
                                    <div id="stripe-card-errors" class="error-message mt-2" role="alert"></div>
                                </div>
                            </div>

                            <!-- Order Notes -->
                            <div class="checkout__input mt-4">
                                <p class="form-label">Order Notes <span class="text-muted">(Optional)</span></p>
                                <textarea name="notes" placeholder="Special instructions or notes about your order..." 
                                          class="form-control-custom" rows="4">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn-place-order" 
                                    {{ $addresses->count() == 0 ? 'disabled' : '' }}>
                                <i class="fa fa-check-circle"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Right Column: Order Summary -->
                <div class="col-lg-5">
                    <div class="order-summary-section">
                        <!-- Cart Items -->
                        <div class="summary-card">
                            <h5 class="summary-title"><i class="fa fa-shopping-cart"></i> Your Order</h5>
                            
                            <div id="cart-loading" class="loading-state">
                                <i class="fa fa-spinner fa-spin"></i>
                                <p>Loading your cart...</p>
                            </div>
                            
                            <div id="cart-table-container" style="display: none;">
                                <div class="cart-items-list">
                                    <table class="cart-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-items-tbody">
                                            <!-- Populated via AJAX -->
                                        </tbody>
                                    </table>
                    </div>
                                
                                <div class="order-totals">
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <span id="checkout-subtotal" class="total-value">$0.00</span>
                </div>
                                    <div class="total-row discount-row" id="checkout-discount-row" style="display:none">
                                        <span>Discount</span>
                                        <span id="checkout-discount" class="discount-value">-$0.00</span>
                                    </div>
                                    <div class="total-row grand-total">
                                        <span>Total</span>
                                        <span id="checkout-total" class="total-amount">$0.00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="empty-cart-message" class="empty-state" style="display: none;">
                                <i class="fa fa-shopping-cart"></i>
                                <p>Your cart is empty</p>
                                <a href="{{ route('shop') }}" class="btn-shop-now">Continue Shopping</a>
                            </div>
                        </div>
                        
                        <!-- Order Summary Info -->
                        <div id="order-summary-box" class="summary-card" style="display: none;">
                            <h5 class="summary-title"><i class="fa fa-user"></i> Customer Details</h5>
                            <div class="info-list">
                                <div class="info-item">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value" id="order-user-name">{{ Auth::user()->name ?? 'Guest' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value" id="order-email">{{ Auth::user()->email ?? 'guest@example.com' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value" id="order-phone">{{ Auth::user()->phone ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item address-item">
                                    <span class="info-label">Address:</span>
                                    <span class="info-value" id="order-address">Please select an address</span>
                                </div>
                            </div>
                        </div>
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

    <style>
        /* ========== Modern Checkout Styles ========== */
        
        /* Form Elements */
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: block;
        }

        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            resize: vertical;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #e7ab3c;
            box-shadow: 0 0 0 3px rgba(231, 171, 60, 0.1);
        }

        /* Address Cards */
        .address-selection {
            margin-bottom: 20px;
        }

        .address-card {
            margin-bottom: 12px;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .address-card input[type="radio"] {
            display: none;
        }

        .address-card-label {
            display: block;
            padding: 20px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .address-card-label:hover {
            border-color: #e7ab3c;
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.15);
            transform: translateY(-2px);
        }

        .address-card input[type="radio"]:checked + .address-card-label {
            border-color: #e7ab3c;
            background: linear-gradient(135deg, #fff9f0 0%, #ffffff 100%);
            box-shadow: 0 4px 16px rgba(231, 171, 60, 0.2);
        }

        .address-card-content {
            position: relative;
        }

        .address-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .address-actions {
            display: inline-flex;
            gap: 8px;
            margin-left: 8px;
        }

        .icon-btn {
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px 6px;
        }

        .icon-btn:hover { color: #e7ab3c; }

        .address-name {
            font-size: 16px;
            color: #1a1a1a;
            font-weight: 600;
        }

        .badge-default {
            background: linear-gradient(135deg, #e7ab3c 0%, #d4a853 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .address-company {
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
        }

        .address-text {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .address-phone {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .address-phone i {
            color: #e7ab3c;
        }

        /* Other Addresses Toggle */
        .other-addresses-wrapper {
            margin-top: 16px;
        }

        .btn-toggle-addresses {
            width: 100%;
            padding: 12px 20px;
            background: #f8f9fa;
            border: 1px dashed #d0d0d0;
            border-radius: 8px;
            color: #e7ab3c;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-toggle-addresses:hover {
            background: #fff9f0;
            border-color: #e7ab3c;
        }

        .btn-toggle-addresses i {
            transition: transform 0.3s ease;
        }

        .btn-toggle-addresses[aria-expanded="true"] i {
            transform: rotate(180deg);
        }

        .other-addresses-list {
            margin-top: 12px;
            padding-top: 12px;
        }

        /* Add Address Button */
        .btn-add-address {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e7ab3c;
            border-radius: 8px;
            color: #e7ab3c;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-address:hover {
            background: #e7ab3c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.3);
        }

        /* Empty Address State */
        .empty-address-state {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #d0d0d0;
        }

        .empty-address-state i {
            font-size: 48px;
            color: #d0d0d0;
            margin-bottom: 16px;
        }

        .empty-address-state p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* Payment Cards */
        .payment-methods {
            display: grid;
            gap: 12px;
        }

        .payment-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .payment-card input[type="radio"] {
            display: none;
        }

        .payment-card-label {
            display: block;
            padding: 18px 20px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .payment-card-label:hover {
            border-color: #e7ab3c;
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.15);
        }

        .payment-card input[type="radio"]:checked + .payment-card-label {
            border-color: #e7ab3c;
            background: linear-gradient(135deg, #fff9f0 0%, #ffffff 100%);
            box-shadow: 0 4px 16px rgba(231, 171, 60, 0.2);
        }

        .payment-card-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .payment-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #e7ab3c 0%, #d4a853 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .payment-icon i {
            font-size: 22px;
            color: white;
        }

        .payment-details {
            flex: 1;
        }

        .payment-name {
            display: block;
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .payment-desc {
            display: block;
            font-size: 12px;
            color: #888;
            line-height: 1.4;
        }

        /* Stripe Elements */
        .stripe-container {
            margin-top: 20px;
        }

        .stripe-card-input {
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }

        .stripe-card-input:focus-within {
            border-color: #e7ab3c;
            box-shadow: 0 0 0 3px rgba(231, 171, 60, 0.1);
        }

        /* Place Order Button */
        .btn-place-order {
            width: 100%;
            padding: 16px 32px;
            margin-top: 30px;
            background: linear-gradient(135deg, #e7ab3c 0%, #d4a853 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(231, 171, 60, 0.3);
        }

        .btn-place-order:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(231, 171, 60, 0.4);
        }

        .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Order Summary Section */
        .order-summary-section {
            position: sticky;
            top: 20px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
        }

        .summary-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-title i {
            color: #e7ab3c;
            font-size: 20px;
        }

        /* Cart Table */
        .cart-items-list {
            margin-bottom: 20px;
        }

        .cart-table {
            width: 100%;
            font-size: 13px;
        }

        .cart-table thead th {
            background: #f8f9fa;
            padding: 12px 8px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 11px;
            border-bottom: 2px solid #e0e0e0;
        }

        .cart-table tbody td {
            padding: 16px 8px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .cart-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            color: #555;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            background: #e7ab3c;
            border-color: #e7ab3c;
            color: white;
        }

        .quantity-input {
            width: 50px;
            height: 28px;
            text-align: center;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Order Totals */
        .order-totals {
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 14px;
        }

        .total-value {
            font-weight: 600;
            color: #333;
        }

        .discount-row {
            color: #28a745;
        }

        .discount-value {
            color: #dc3545;
            font-weight: 600;
        }

        .grand-total {
            margin-top: 12px;
            padding-top: 16px;
            border-top: 2px solid #e7ab3c;
            font-size: 18px;
            font-weight: 700;
        }

        .total-amount {
            color: #e7ab3c;
            font-size: 20px;
        }

        /* Info List */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            min-width: 80px;
        }

        .info-value {
            color: #333;
            font-size: 13px;
            text-align: right;
            flex: 1;
        }

        .address-item .info-value {
            max-width: 220px;
            word-wrap: break-word;
        }

        /* Loading and Empty States */
        .loading-state {
            text-align: center;
            padding: 40px 20px;
            color: #e7ab3c;
        }

        .loading-state i {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .loading-state p {
            color: #888;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #d0d0d0;
            margin-bottom: 16px;
        }

        .empty-state p {
            color: #888;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .btn-shop-now {
            display: inline-block;
            padding: 12px 24px;
            background: #e7ab3c;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-shop-now:hover {
            background: #d4a853;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.3);
            color: white;
        }

        /* Error Messages */
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 8px;
            display: block;
        }

        /* Hide number input arrows */
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .quantity-input[type=number] {
            -moz-appearance: textfield;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .order-summary-section {
                position: static;
                margin-top: 40px;
            }
        }

        @media (max-width: 767px) {
            .summary-card {
                padding: 20px;
            }

            .address-card-label,
            .payment-card-label {
                padding: 16px;
            }

            .btn-place-order {
                padding: 14px 24px;
                font-size: 14px;
            }

            .cart-table {
                font-size: 12px;
            }

            .cart-table thead th {
                padding: 10px 6px;
                font-size: 10px;
            }

            .cart-table tbody td {
                padding: 12px 6px;
            }
        }

        /* Primary Button Variant */
        .btn-primary-custom {
            padding: 12px 24px;
            background: linear-gradient(135deg, #e7ab3c 0%, #d4a853 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 171, 60, 0.3);
        }
    </style>

    <!-- Js Plugins -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}" onerror="loadJQueryFromCDN()"></script>
    <script>
        function loadJQueryFromCDN() {
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.3.1.min.js';
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

    <!-- Payment & Checkout Scripts -->
    <script>
    (function() {
        var stripeJsLoaded = false;
        var razorpayJsLoaded = false;
        var STRIPE_PUBLISHABLE_KEY = "{{ config('services.stripe.key') }}";
        var stripeInstance = null;
        var stripeElements = null;
        var stripeCardElement = null;

        function loadScript(src) {
            return new Promise(function(resolve, reject) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        function showLoading(btn, on) {
            if (!btn) return;
            btn.disabled = !!on;
            if (on) {
                btn.setAttribute('data-original-text', btn.innerHTML);
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            } else {
                btn.innerHTML = btn.getAttribute('data-original-text') || '<i class="fa fa-check-circle"></i> Place Order';
            }
        }

        function toast(msg, type) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: type === 'error' ? 'Error' : 'Notice',
                    html: '<div style="font-size:14px;">' + msg + '</div>',
                    icon: type === 'error' ? 'error' : 'info',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            } else {
                alert(msg);
            }
        }

        // Update cart count in navbar
        function updateCartCount(count) {
            var cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = count || 0;
            }
        }

        // Load cart data via AJAX
        function loadCartData() {
            fetch('/cart/data', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                displayCartData(data);
            })
            .catch(error => {
                console.error('Error loading cart data:', error);
                document.getElementById('cart-loading').style.display = 'none';
                document.getElementById('empty-cart-message').style.display = 'block';
            });
        }

        // Global flag to prevent duplicate event binding
        var quantityEventsBound = false;

        // Display cart data
        function displayCartData(data) {
            const loadingEl = document.getElementById('cart-loading');
            const tableContainer = document.getElementById('cart-table-container');
            const emptyMessage = document.getElementById('empty-cart-message');
            const tbody = document.getElementById('cart-items-tbody');
            const orderSummaryBox = document.getElementById('order-summary-box');
            
            if (loadingEl) loadingEl.style.display = 'none';
            
            if (!data.items || data.items.length === 0) {
                if (emptyMessage) emptyMessage.style.display = 'block';
                return;
            }
            
            if (tableContainer) tableContainer.style.display = 'block';
            if (orderSummaryBox) orderSummaryBox.style.display = 'block';
            
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            data.items.forEach(function(item) {
                const row = document.createElement('tr');
                row.setAttribute('data-product-id', item.product_id);
                
                const imageSrc = item.image_url || '{{ asset("img/product/product-1.jpg") }}';
                const itemName = (item.name || 'Product').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                
                row.innerHTML = '<td>' +
                    '<div style="display: flex; align-items: center; gap: 10px;">' +
                    '<img src="' + imageSrc + '" alt="' + itemName + '" ' +
                    'style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px; flex-shrink: 0;" ' +
                    'onerror="this.src=\'{{ asset("img/product/product-1.jpg") }}\';">' +
                    '<div style="flex: 1; min-width: 0;">' +
                    '<div style="font-weight: 600; font-size: 13px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + itemName + '</div>' +
                    '<div style="font-size: 12px; color: #888; margin-top: 2px;">$' + item.price.toFixed(2) + ' each</div>' +
                    '</div>' +
                    '</div>' +
                    '</td>' +
                    '<td class="text-center">' +
                    '<div class="quantity-controls">' +
                    '<button type="button" class="qty-btn dec" data-product-id="' + item.product_id + '">-</button>' +
                    '<input type="number" value="' + item.quantity + '" min="1" class="quantity-input" data-product-id="' + item.product_id + '">' +
                    '<button type="button" class="qty-btn inc" data-product-id="' + item.product_id + '">+</button>' +
                    '</div>' +
                    '</td>' +
                    '<td class="text-right">' +
                    '<span class="item-total" style="font-weight: 600; color: #333;">$' + (item.price * item.quantity).toFixed(2) + '</span>' +
                    '<input type="hidden" class="item-unit-price" data-unit-price="' + item.price.toFixed(2) + '">' +
                    '</td>';
                
                tbody.appendChild(row);
            });
            
            updateCheckoutTotals(data);
            
            if (!quantityEventsBound) {
                bindQuantityEvents();
                quantityEventsBound = true;
            }
        }

        // Update checkout totals
        function updateCheckoutTotals(data) {
            const subtotalEl = document.getElementById('checkout-subtotal');
            const totalEl = document.getElementById('checkout-total');
            
            if (subtotalEl) {
                subtotalEl.textContent = '$' + data.subtotal.toFixed(2);
            }
            
            const discountRow = document.getElementById('checkout-discount-row');
            const discountAmount = document.getElementById('checkout-discount');
            
            if (data.discountAmount > 0) {
                if (discountRow) discountRow.style.display = 'flex';
                if (discountAmount) discountAmount.textContent = '-$' + data.discountAmount.toFixed(2);
            } else {
                if (discountRow) discountRow.style.display = 'none';
            }
            
            if (totalEl) {
                totalEl.textContent = '$' + data.total.toFixed(2);
            }
        }

        // Bind quantity events (only once)
        function bindQuantityEvents() {
            var tbody = document.getElementById('cart-items-tbody');
            if (!tbody) return;
            
            tbody.removeEventListener('change', handleQuantityChange);
            tbody.removeEventListener('click', handleQuantityClick);
            
            tbody.addEventListener('change', handleQuantityChange);
            tbody.addEventListener('click', handleQuantityClick);
        }
        
        function handleQuantityChange(e) {
            if (e.target.classList.contains('quantity-input')) {
                const productId = e.target.getAttribute('data-product-id');
                const quantity = parseInt(e.target.value);
                if (quantity && quantity > 0) {
                    updateCartQuantity(productId, quantity);
                }
            }
        }
        
        function handleQuantityClick(e) {
            if (e.target.classList.contains('qty-btn')) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = e.target.getAttribute('data-product-id');
                const input = e.target.parentElement.querySelector('.quantity-input[data-product-id="' + productId + '"]');
                if (!input) return;
                
                const currentQty = parseInt(input.value) || 1;
                let newQty = currentQty;
                
                if (e.target.classList.contains('inc')) {
                    newQty = currentQty + 1;
                } else if (e.target.classList.contains('dec') && currentQty > 1) {
                    newQty = currentQty - 1;
                }
                
                if (newQty !== currentQty) {
                    input.value = newQty;
                    
                    const row = input.closest('tr');
                    if (row) {
                        const unitPriceInput = row.querySelector('.item-unit-price');
                        if (unitPriceInput) {
                            const unitPrice = parseFloat(unitPriceInput.getAttribute('data-unit-price'));
                            const newTotal = unitPrice * newQty;
                            const totalSpan = row.querySelector('.item-total');
                            if (totalSpan) {
                                totalSpan.textContent = '$' + newTotal.toFixed(2);
                            }
                        }
                    }
                    
                    updateCartQuantity(productId, newQty);
                }
            }
        }

        // Update cart quantity via AJAX
        function updateCartQuantity(productId, quantity) {
            fetch('/cart/' + productId, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ quantity: quantity })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cart_data) {
                    updateCheckoutTotals(data.cart_data);
                    
                    const cartCountEl = document.getElementById('cart-count');
                    if (cartCountEl && data.cart_count !== undefined) {
                        cartCountEl.textContent = data.cart_count;
                    }
                } else {
                    console.error('Cart update failed:', data.message);
                    toast('Error updating quantity', 'error');
                }
            })
            .catch(error => {
                console.error('Error updating quantity:', error);
                toast('Error updating quantity', 'error');
            });
        }

        // Handle address selection
        function handleAddressSelection() {
            document.addEventListener('change', function(e) {
                if (e.target.name === 'shipping_address_id') {
                    updateOrderSummaryAddress(e.target.value);
                }
            });
            
            const selectedAddress = document.querySelector('input[name="shipping_address_id"]:checked');
            if (selectedAddress) {
                updateOrderSummaryAddress(selectedAddress.value);
            }
        }

        // Update order summary address
        function updateOrderSummaryAddress(addressId) {
            if (!addressId) {
                document.getElementById('order-address').textContent = 'Please select an address';
                document.getElementById('order-phone').textContent = 'N/A';
                return;
            }
            
            const addressInput = document.querySelector('input[name="shipping_address_id"][value="' + addressId + '"]');
            if (!addressInput) return;
            
            const addressLabel = addressInput.nextElementSibling;
            const addressContent = addressLabel.querySelector('.address-card-content');
            
            if (addressContent) {
                const addressText = addressContent.querySelector('.address-text');
                const phoneElement = addressContent.querySelector('.address-phone');
                
                if (addressText) {
                    document.getElementById('order-address').textContent = addressText.textContent.trim();
                }
                
                if (phoneElement) {
                    const phoneText = phoneElement.textContent.trim();
                    const phoneNumber = phoneText.replace(/^.*?(\d+.*)$/, '$1');
                    document.getElementById('order-phone').textContent = phoneNumber;
                }
            }
        }

        function toggleStripeElementsVisible(show) {
            var el = document.getElementById('stripe-elements-container');
            if (el) el.style.display = show ? 'block' : 'none';
        }

        async function ensureStripeElementsReady() {
            try {
                if (!stripeJsLoaded) {
                    await loadScript('https://js.stripe.com/v3/');
                    stripeJsLoaded = true;
                }
                if (!stripeInstance) {
                    var key = STRIPE_PUBLISHABLE_KEY || '';
                    if (!key) {
                        console.error('Stripe publishable key is missing');
                        throw new Error('Stripe publishable key missing');
                    }
                    stripeInstance = window.Stripe(key);
                }
                if (!stripeElements) {
                    stripeElements = stripeInstance.elements();
                }
                if (!stripeCardElement) {
                    var cardElementContainer = document.getElementById('stripe-card-element');
                    if (!cardElementContainer) {
                        console.error('Stripe card element container not found');
                        return;
                    }
                    stripeCardElement = stripeElements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#32325d',
                                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                                '::placeholder': {
                                    color: '#aab7c4'
                                }
                            },
                            invalid: {
                                color: '#fa755a',
                                iconColor: '#fa755a'
                            }
                        }
                    });
                    stripeCardElement.mount('#stripe-card-element');
                    stripeCardElement.on('change', function(event) {
                        var err = document.getElementById('stripe-card-errors');
                        if (err) {
                            err.textContent = event.error ? event.error.message : '';
                        }
                    });
                    console.log('Stripe card element mounted successfully');
                }
            } catch (error) {
                console.error('Error initializing Stripe elements:', error);
                throw error;
            }
        }

        async function initiateStripe(orderIds) {
            try {
                await ensureStripeElementsReady();
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/payment/stripe/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ order_ids: orderIds })
                });
                
                if (!res.ok) throw new Error('Stripe initiate failed');
                const data = await res.json();
                
                const { error, paymentIntent } = await stripeInstance.confirmCardPayment(data.clientSecret, {
                    payment_method: { card: stripeCardElement }
                });
                
                if (error) {
                    var err = document.getElementById('stripe-card-errors');
                    if (err) err.textContent = error.message || 'Payment failed';
                    throw new Error(error.message || 'Payment failed');
                }
                // Call backend confirm (no webhook) to send email, mark paid, clear cart
                try {
                    await fetch('/payment/stripe/confirm', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ payment_intent_id: paymentIntent.id })
                    });
                } catch (e) { /* ignore non-blocking */ }
                
                // Payment successful - update cart count and show success
                updateCartCount(0);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Payment Successful!',
                        html: '<div style="font-size:14px;">✅ Your payment has been processed successfully.<br>Order confirmation email has been sent.</div>',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Go to My Orders',
                        cancelButtonText: 'Close',
                        customClass: { 
                            confirmButton: 'btn btn-primary', 
                            cancelButton: 'btn btn-secondary' 
                        },
                        buttonsStyling: false,
                        allowOutsideClick: false
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            window.location.href = '/myorder';
                        } else {
                            window.location.href = '/';
                        }
                    });
                } else {
                    window.location.href = '/myorder';
                }
            } catch (error) {
                console.error('Stripe payment error:', error);
                throw error;
            }
        }

        async function initiateRazorpay(orderIds) {
            try {
                if (!razorpayJsLoaded) {
                    await loadScript('https://checkout.razorpay.com/v1/checkout.js');
                    razorpayJsLoaded = true;
                }
                
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/payment/razorpay/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ order_ids: orderIds })
                });
                
                if (!res.ok) throw new Error('Razorpay initiate failed');
                const data = await res.json();
                
                const userName = (document.getElementById('order-user-name') || {}).textContent || '';
                const userEmail = (document.getElementById('order-email') || {}).textContent || '';

                return new Promise(function(resolve, reject) {
                    const options = {
                        key: data.key,
                        amount: data.amount,
                        currency: data.currency,
                        order_id: data.razorpayOrderId,
                        prefill: {
                            name: userName || undefined,
                            email: userEmail || undefined,
                        },
                        method: {
                            netbanking: true,
                            card: true,
                            upi: true,
                            wallet: true
                        },
                        handler: function(response) { 
                            console.log('Razorpay payment successful:', response);
                            resolve(response); 
                        },
                        modal: {
                            ondismiss: function() { 
                                console.log('Razorpay payment cancelled by user');
                                reject(new Error('Payment cancelled')); 
                            }
                        }
                    };
                    const rz = new window.Razorpay(options);
                    rz.open();
                }).then(async function(resp) {
                    console.log('Confirming Razorpay payment...');
                    
                    try {
                        const confirmRes = await fetch('/payment/razorpay/confirm', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                razorpay_order_id: data.razorpayOrderId,
                                razorpay_payment_id: resp.razorpay_payment_id
                            })
                        });
                        
                        const confirmJson = await confirmRes.json();
                        console.log('Razorpay confirmation response:', confirmJson);
                        
                        if (!confirmRes.ok || (confirmJson && confirmJson.success === false)) {
                            throw new Error(confirmJson.message || 'Confirmation failed');
                        }
                        
                        // Payment confirmed successfully - update cart count
                        updateCartCount(0);
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Payment Successful!',
                                html: '<div style="font-size:14px;">✅ Your payment has been processed successfully.<br>Order confirmation email has been sent.</div>',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Go to My Orders',
                                cancelButtonText: 'Close',
                                customClass: { 
                                    confirmButton: 'btn btn-primary', 
                                    cancelButton: 'btn btn-secondary' 
                                },
                                buttonsStyling: false,
                                allowOutsideClick: false
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    window.location.href = '/myorder';
                                } else {
                                    window.location.href = '/';
                                }
                            });
                        } else {
                            window.location.href = '/myorder';
                        }
                    } catch (e) {
                        console.error('Razorpay confirmation error:', e);
                        throw new Error('Payment confirmation failed: ' + e.message);
                    }
                });
            } catch (error) {
                console.error('Razorpay payment error:', error);
                throw error;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Checkout page loaded');
            
            var form = document.querySelector('.checkout__form form');
            if (!form) {
                console.error('Checkout form not found');
                return;
            }
            var submitBtn = form.querySelector('button[type="submit"]');
            
            loadCartData();
            handleAddressSelection();
            
            // Payment method change handler
            var paymentInputs = document.querySelectorAll('input[name="payment_method_id"]');
            paymentInputs.forEach(function(input) {
                input.addEventListener('change', function(e) {
                    var name = e.target.getAttribute('data-method-name');
                    console.log('Payment method changed to:', name);
                    
                    if (name === 'stripe') {
                        toggleStripeElementsVisible(true);
                        ensureStripeElementsReady().catch(function(err) {
                            console.error('Failed to initialize Stripe:', err);
                        });
                    } else {
                        toggleStripeElementsVisible(false);
                    }
                });
            });
            
            // Initialize Stripe visibility on load
            var checkedPayment = document.querySelector('input[name="payment_method_id"]:checked');
            if (checkedPayment) {
                var name = checkedPayment.getAttribute('data-method-name');
                console.log('Initial payment method:', name);
                
                if (name === 'stripe') {
                    toggleStripeElementsVisible(true);
                    setTimeout(function() {
                        ensureStripeElementsReady().catch(function(err) {
                            console.error('Failed to initialize Stripe on load:', err);
                        });
                    }, 100);
                }
            }

            // Address edit/delete handlers
            document.addEventListener('click', async function(e) {
                var btn;
                if (e.target.closest) {
                    btn = e.target.closest('.btn-edit-address, .btn-delete-address');
                }
                if (!btn) return;
                e.preventDefault();
                var id = btn.getAttribute('data-id');
                if (btn.classList.contains('btn-edit-address')) {
                    if (typeof window.openAddressModal === 'function') {
                        window.openAddressModal(id);
                    }
                } else if (btn.classList.contains('btn-delete-address')) {
                    if (!confirm('Delete this address?')) return;
                    try {
                        const res = await fetch('/addresses/' + id, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: new URLSearchParams({ _method: 'DELETE' })
                        });
                        if (!res.ok) throw new Error('Failed to delete');
                        var input = document.querySelector('input#address_' + id);
                        if (input) {
                            var card = input.closest('.address-card');
                            if (card && card.parentNode) card.parentNode.removeChild(card);
                        }
                        var selected = document.querySelector('input[name="shipping_address_id"]:checked');
                        if (!selected) {
                            var firstRadio = document.querySelector('input[name="shipping_address_id"]');
                            if (firstRadio) { 
                                firstRadio.checked = true; 
                                updateOrderSummaryAddress(firstRadio.value); 
                            } else { 
                                updateOrderSummaryAddress(null); 
                            }
                        }
                    } catch (err) {
                        alert('Unable to delete address.');
                    }
                }
            });
            
            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    // Check if address is selected
                    const selectedAddress = document.querySelector('input[name="shipping_address_id"]:checked');
                    if (!selectedAddress) {
                        toast('Please select a shipping address.', 'error');
                        return;
                    }
                    
                    showLoading(submitBtn, true);
                    const formData = new FormData(form);
                    
                    // Debug: Log form data
                    console.log('Form data being sent:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                    
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    const contentType = res.headers.get('content-type') || '';
                    let data;
                    if (contentType.includes('application/json')) {
                        data = await res.json();
                    } else {
                        // Try to parse anyway; if fails, stay on checkout and show error
                        try {
                            const text = await res.text();
                            data = JSON.parse(text);
                        } catch (_) {
                            toast('Unexpected response. Please try again.', 'error');
                            return;
                        }
                    }
                    if (!data || !data.success) {
                        throw new Error(data.message || 'Checkout initiation failed');
                    }
                    
                    const orderIds = data.order_ids || [];
                    const paymentMethod = data.payment_method;
                    
                    console.log('Order initiated, payment method:', paymentMethod);
                    
                    if (paymentMethod === 'stripe') {
                        try {
                            await initiateStripe(orderIds);
                        } catch (error) {
                            console.error('Stripe payment failed:', error);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Payment Failed',
                                    html: '<div style="font-size:14px;">❌ Payment failed. Please try again.<br><small>' + error.message + '</small></div>',
                                    icon: 'error',
                                    confirmButtonText: 'Try Again',
                                    customClass: { confirmButton: 'btn btn-primary' },
                                    buttonsStyling: false,
                                    allowOutsideClick: false
                                }).then(function() { /* stay on checkout; cart persists */ });
                            } else {
                                alert('Payment failed: ' + error.message);
                                window.location.href = '/checkout';
                            }
                        }
                    } else if (paymentMethod === 'razorpay') {
                        try {
                            await initiateRazorpay(orderIds);
                        } catch (error) {
                            console.error('Razorpay payment failed:', error);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Payment Failed',
                                    html: '<div style="font-size:14px;">❌ Payment failed. Please try again.<br><small>' + error.message + '</small></div>',
                                    icon: 'error',
                                    confirmButtonText: 'Try Again',
                                    customClass: { confirmButton: 'btn btn-primary' },
                                    buttonsStyling: false,
                                    allowOutsideClick: false
                                }).then(function() { /* stay on checkout; cart persists */ });
                            } else {
                                alert('Payment failed: ' + error.message);
                                window.location.href = '/checkout';
                            }
                        }
                    } else if (paymentMethod === 'cod') {
                        // COD successful - update cart count
                        updateCartCount(0);
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Order Placed Successfully!',
                                html: '<div style="font-size:14px;">✅ Thank you for your order!<br>Your order has been successfully placed.<br>Order confirmation email has been sent.</div>',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Go to My Orders',
                                cancelButtonText: 'Close',
                                customClass: { 
                                    confirmButton: 'btn btn-primary', 
                                    cancelButton: 'btn btn-secondary' 
                                },
                                buttonsStyling: false,
                                allowOutsideClick: false
                            }).then(function(result) { 
                        if (result.isConfirmed) {
                            window.location.href = '/myorder';
                        } else {
                            window.location.href = '/';
                        }
                            });
                        } else {
                            window.location.href = '/myorder';
                        }
                    } else {
                        throw new Error('Unsupported payment method');
                    }
                } catch (err) {
                    console.error('Checkout error:', err);
                    toast(err && err.message ? err.message : 'Something went wrong. Please try again.', 'error');
                } finally {
                    showLoading(submitBtn, false);
                }
            });
        });
    })();
    </script>

    <!-- Address Modal JavaScript -->
    <script>
    (function() {
        var INIT_KEY = '__address_modal_initialized__';
        // Address modal script (production mode)

        window.openAddressModal = function(id) {
            if (!window[INIT_KEY]) safeInitialize();
            if (typeof jQuery === 'undefined' || !document.getElementById('addressModal')) return;
            
            resetAddressForm();
            if (id) {
                $('#addressModalLabel').text('Edit Address');
                $('#addressMethod').val('PUT');
                $('#addressId').val(id);
                $.ajax({
                    url: '/addresses/' + id + '/edit',
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(response) {
                    if (response && response.success) {
                        fillAddressForm(response.address || {});
                    }
                }).fail(function() {
                    alert('Failed to load address data');
                }).always(function() {
                    $('#addressModal').modal('show');
                });
            } else {
                $('#addressModalLabel').text('Add New Address');
                $('#addressMethod').val('POST');
                $('#addressId').val('');
                $('#addressModal').modal('show');
            }
        };

        window.ADDRESS_LOCATION_DATA = {
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
            if (window[INIT_KEY]) return;
            if (typeof jQuery === 'undefined') {
                setTimeout(safeInitialize, 50);
                return;
            }

            $(function() {
                if (window[INIT_KEY]) return;
                bindDropdownHandlers();
                bindValidationHandlers();
                bindModalHandlers();
                bindSaveHandler();
                window[INIT_KEY] = true;
            });
        }

        function bindDropdownHandlers() {
            $(document).on('change', '#country', function() {
                var country = $(this).val();
                var stateSelect = $('#state');
                var citySelect = $('#city');
                
                stateSelect.html('<option value="">Select State</option>');
                citySelect.html('<option value="">Select City</option>');

                var data = window.ADDRESS_LOCATION_DATA || {};
                if (!country || !data[country]) {
                    validateAddressForm();
                    return;
                }
                
                var states = Object.keys((data[country] && data[country].states) || {});
                states.forEach(function(s) {
                    stateSelect.append('<option value="' + s + '">' + s + '</option>');
                });
                validateAddressForm();
            });

            $(document).on('change', '#state', function() {
                var country = $('#country').val();
                var state = $(this).val();
                var citySelect = $('#city');
                
                citySelect.html('<option value="">Select City</option>');

                var data = window.ADDRESS_LOCATION_DATA || {};
                if (!country || !state || !data[country] || !data[country].states[state]) {
                    validateAddressForm();
                    return;
                }
                
                (data[country].states[state] || []).forEach(function(city) {
                    citySelect.append('<option value="' + city + '">' + city + '</option>');
                });
                validateAddressForm();
            });

            $(document).on('click', '#addressModal .nice-select .option', function() {
                var select = $(this).closest('.nice-select').prev('select');
                if (select && select.length) {
                    setTimeout(function() {
                        select.trigger('change');
                    }, 0);
                }
            });
        }

        function bindValidationHandlers() {
            $(document).on('input', '#phone', function() {
                var value = String($(this).val() || '').replace(/\D/g, '');
                var code = $('#country_code').val();
                if ((code === '+91' || code === '+1') && value.length > 10) {
                    value = value.substring(0, 10);
                }
                $(this).val(value);
                validateAddressForm();
            });
            
            $(document).on('input change', '#addressForm input, #addressForm select', function() {
                validateAddressForm();
            });
        }

        function bindModalHandlers() {
            $('#addressModal').on('shown.bs.modal', function() {
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
                if (!form) return;
                
                var id = $('#addressId').val();
                var method = $('#addressMethod').val();
                var url = id ? '/addresses/' + id : '/addresses';
                var formData = new FormData(form);
                
                if (id) formData.append('_method', method);
                
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
                    }
                }).done(function(response) {
                    if (response && response.success) {
                        $('#addressModal').modal('hide');
                        location.reload();
                    } else {
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
            // Email: prefer address email; else fallback to user's email
            try {
                var userEmail = {!! json_encode(Auth::user()->email ?? '') !!};
                var emailToSet = (address.email && String(address.email).trim()) ? address.email : userEmail;
                $('#email').val(emailToSet || '');
            } catch(e) { $('#email').val(address.email || ''); }

            var country = address.country || '';
            var state = address.state || '';
            var city = address.city || '';
            
            $('#country').val(country).trigger('change');
            if (state) setTimeout(function() {
                $('#state').val(state).trigger('change');
            }, 0);
            if (city) setTimeout(function() {
                $('#city').val(city);
            }, 0);

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
            // Autofill from current user: first name from user's full name, last name blank, email from user
            try {
                var fullName = {!! json_encode(Auth::user()->name ?? '') !!};
                var email = {!! json_encode(Auth::user()->email ?? '') !!};
                var first = '';
                if (fullName) {
                    var parts = String(fullName).trim().split(/\s+/);
                    if (parts.length) first = parts[0];
                }
                $('#first_name').val(first);
                $('#last_name').val('');
                $('#email').val(email || '');
            } catch(e) {}
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

        safeInitialize();
    })();
    </script>

</body>
</html>