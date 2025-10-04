<x-header />

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Add New Address</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('addresses.index') }}">My Addresses</a>
                        <span>Add New Address</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Address Form Section Begin -->
<section class="checkout spad">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout__form">
                    <h4>Add New Shipping Address</h4>
                    <form method="POST" action="{{ route('addresses.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>First Name<span>*</span></p>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                                    @error('first_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>Last Name<span>*</span></p>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                                    @error('last_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input">
                            <p>Company</p>
                            <input type="text" name="company" value="{{ old('company') }}" placeholder="Company (optional)">
                            @error('company')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout__input">
                            <p>Address Line 1<span>*</span></p>
                            <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" placeholder="Street address" required>
                            @error('address_line_1')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout__input">
                            <p>Address Line 2</p>
                            <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="Apartment, suite, unit, etc. (optional)">
                            @error('address_line_2')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>City<span>*</span></p>
                                    <input type="text" name="city" value="{{ old('city') }}" required>
                                    @error('city')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>State<span>*</span></p>
                                    <input type="text" name="state" value="{{ old('state') }}" required>
                                    @error('state')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>Postal Code<span>*</span></p>
                                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" required>
                                    @error('postal_code')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkout__input">
                                    <p>Country<span>*</span></p>
                                    <input type="text" name="country" value="{{ old('country') }}" required>
                                    @error('country')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="checkout__input">
                            <p>Phone Number<span>*</span></p>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout__input">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" value="1" id="is_default" class="form-check-input" 
                                       {{ old('is_default') ? 'checked' : '' }}>
                                <label for="is_default" class="form-check-label">
                                    Set as default shipping address
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="site-btn">Add Address</button>
                            <a href="{{ route('addresses.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Address Form Section End -->

<x-footer />

<style>
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.form-actions .btn {
    padding: 12px 30px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}

.checkout__input input[type="text"],
.checkout__input input[type="tel"] {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.checkout__input input[type="text"]:focus,
.checkout__input input[type="tel"]:focus {
    outline: none;
    border-color: #e7ab3c;
    box-shadow: 0 0 0 2px rgba(231, 171, 60, 0.1);
}

.form-check-input {
    margin-right: 8px;
}

.form-check-label {
    font-weight: 500;
    color: #333;
}
</style>
