<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" role="dialog" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">Add New Address</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    @csrf
                    <input type="hidden" id="addressId" name="address_id">
                    <input type="hidden" id="addressMethod" value="POST">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" name="first_name" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                    </div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                    </div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select class="form-control" id="country_code" style="max-width: 80px;">
                                    <option value="+91">🇮🇳 +91</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+33">🇫🇷 +33</option>
                                    <option value="+49">🇩🇪 +49</option>
                                </select>
                            </div>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" required>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-phone"></i></span>
                            </div>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            </div>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country">Country <span class="text-danger">*</span></label>
                                <select class="form-control @error('country') is-invalid @enderror" 
                                        id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="India">India</option>
                                    <option value="United States">United States</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="France">France</option>
                                    <option value="Germany">Germany</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Australia">Australia</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State <span class="text-danger">*</span></label>
                                <select class="form-control @error('state') is-invalid @enderror" 
                                        id="state" name="state" required>
                                    <option value="">Select State</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City <span class="text-danger">*</span></label>
                                <select class="form-control @error('city') is-invalid @enderror" 
                                        id="city" name="city" required>
                                    <option value="">Select City</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Pin Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                   id="postal_code" name="postal_code" placeholder="eg. 900001" required>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line_1">Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('address_line_1') is-invalid @enderror" 
                                   id="address_line_1" name="address_line_1" 
                                   placeholder="eg. 123 Elm Street, Springfield" required>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-map-marker-alt"></i></span>
                            </div>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line_2">Address Line 2</label>
                        <input type="text" class="form-control @error('address_line_2') is-invalid @enderror" 
                               id="address_line_2" name="address_line_2" 
                               placeholder="Apartment, suite, unit, etc. (optional)">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" class="form-control @error('company') is-invalid @enderror" 
                               id="company" name="company" placeholder="Company (optional)">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1">
                            <label class="form-check-label" for="is_default">
                                Set As Primary
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addressSaveBtn" onclick="saveAddress()" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="addressSpinner" role="status" aria-hidden="true"></span>
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 20px 30px;
}

.modal-title {
    font-weight: 600;
    color: #333;
    margin: 0;
}

.modal-body {
    padding: 30px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 20px 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    display: block;
}

.input-group .form-control {
    border-right: none;
}

.input-group-append .input-group-text {
    background: #f8f9fa;
    border-left: none;
    color: #6c757d;
}

.form-control:focus {
    border-color: #e7ab3c;
    box-shadow: 0 0 0 0.2rem rgba(231, 171, 60, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.btn-primary {
    background-color: #e7ab3c;
    border-color: #e7ab3c;
    font-weight: 600;
    padding: 10px 30px;
    border-radius: 6px;
}

.btn-primary:hover {
    background-color: #d19c2b;
    border-color: #d19c2b;
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
    opacity: 0.65;
}

.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 6px;
}

.text-danger {
    color: #dc3545 !important;
}

.close {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #000;
    opacity: 0.5;
}

.close:hover {
    opacity: 0.75;
}

/* Fix dropdown styling */
.form-control {
    appearance: auto;
    -webkit-appearance: menulist;
    -moz-appearance: menulist;
}

.form-control:focus {
    border-color: #e7ab3c;
    box-shadow: 0 0 0 0.2rem rgba(231, 171, 60, 0.25);
    outline: none;
}
</style>

