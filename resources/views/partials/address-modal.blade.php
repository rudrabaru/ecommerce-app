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

<script>
$(document).ready(function() {
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
    $('#country').on('change', function() {
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
    $('#state').on('change', function() {
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
    $('#city').on('change', function() {
        validateAddressForm();
    });

    // Phone number formatting
    $('#phone').on('input', function() {
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
    $('#country_code').on('change', function() {
        validateAddressForm();
    });

    // Form validation function
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

    // Real-time validation on input change
    $(document).on('input change', '#addressForm input, #addressForm select', function() {
        validateAddressForm();
    });
});
</script>
