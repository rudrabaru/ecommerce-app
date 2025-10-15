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
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select class="form-control address-dropdown" id="country_code" name="country_code" style="max-width: 120px;" data-no-nice-select="1">
                                    <option value="">Select Code</option>
                                </select>
                            </div>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country">Country <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="country" name="country_id" required data-no-nice-select="1">
                                    <option value="">Select Country</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="state" name="state_id" required disabled data-no-nice-select="1">
                                    <option value="">Select State</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="city" name="city_id" required disabled data-no-nice-select="1">
                                    <option value="">Select City</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Pin Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="eg. 900001" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line_1">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address_line_1" name="address_line_1" 
                               placeholder="eg. 123 Elm Street, Springfield" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line_2">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line_2" name="address_line_2" 
                               placeholder="Apartment, suite, unit, etc. (optional)">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" class="form-control" id="company" name="company" placeholder="Company (optional)">
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

<!-- Address Modal JavaScript -->
<script>
// Wait for jQuery to be available
function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        callback();
    } else {
        console.log('[AddressModal] Waiting for jQuery...');
        setTimeout(() => waitForJQuery(callback), 100);
    }
}

waitForJQuery(function() {
    console.log('[AddressModal] jQuery available, starting initialization...');
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('[AddressModal] DOM ready, initializing address modal...');
        
        // Initialize dropdowns immediately
        loadPhoneCodes();
        loadCountries();
        
        // Bind event handlers
        bindEventHandlers();
        
        // Prevent nice-select from interfering with our dropdowns
        preventNiceSelectInterference();
        
        console.log('[AddressModal] Address modal initialized successfully');
    });
});

// Current user context for autofill (server-provided)
try {
    window.ADDRESS_MODAL_USER = {
        name: {!! json_encode(Auth::user()->name ?? '') !!},
        email: {!! json_encode(Auth::user()->email ?? '') !!}
    };
} catch (e) {
    window.ADDRESS_MODAL_USER = window.ADDRESS_MODAL_USER || { name: '', email: '' };
}

// Comprehensive validation rules
const VALIDATION_RULES = {
    first_name: {
        required: true,
        minLength: 2,
        maxLength: 50,
        pattern: /^[a-zA-Z\s'-]+$/,
        messages: {
            required: 'First name is required',
            minLength: 'First name must be at least 2 characters',
            maxLength: 'First name cannot exceed 50 characters',
            pattern: 'First name can only contain letters, spaces, hyphens, and apostrophes'
        }
    },
    last_name: {
        required: true,
        minLength: 2,
        maxLength: 50,
        pattern: /^[a-zA-Z\s'-]+$/,
        messages: {
            required: 'Last name is required',
            minLength: 'Last name must be at least 2 characters',
            maxLength: 'Last name cannot exceed 50 characters',
            pattern: 'Last name can only contain letters, spaces, hyphens, and apostrophes'
        }
    },
    company: {
        required: false,
        minLength: 2,
        maxLength: 100,
        pattern: /^[a-zA-Z0-9\s.,'&()-]+$/,
        messages: {
            minLength: 'Company name must be at least 2 characters',
            maxLength: 'Company name cannot exceed 100 characters',
            pattern: 'Company name contains invalid characters'
        }
    },
    email: {
        required: false,
        pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        messages: {
            pattern: 'Please enter a valid email address'
        }
    },
    country_code: {
        required: true,
        messages: {
            required: 'Country code is required'
        }
    },
    phone: {
        required: true,
        minLength: 6,
        maxLength: 15,
        pattern: /^\d+$/,
        messages: {
            required: 'Phone number is required',
            minLength: 'Phone number must be at least 6 digits',
            maxLength: 'Phone number cannot exceed 15 digits',
            pattern: 'Phone number can only contain digits'
        }
    },
    country: {
        required: true,
        messages: {
            required: 'Country is required'
        }
    },
    state: {
        required: true,
        messages: {
            required: 'State is required'
        }
    },
    city: {
        required: true,
        messages: {
            required: 'City is required'
        }
    },
    postal_code: {
        required: true,
        minLength: 3,
        maxLength: 10,
        pattern: /^[0-9A-Za-z\s-]+$/,
        messages: {
            required: 'Postal code is required',
            minLength: 'Postal code must be at least 3 characters',
            maxLength: 'Postal code cannot exceed 10 characters',
            pattern: 'Postal code can only contain letters, numbers, spaces, and hyphens'
        }
    },
    address_line_1: {
        required: true,
        minLength: 5,
        maxLength: 200,
        pattern: /^[a-zA-Z0-9\s.,'#/-]+$/,
        messages: {
            required: 'Address line 1 is required',
            minLength: 'Address must be at least 5 characters',
            maxLength: 'Address cannot exceed 200 characters',
            pattern: 'Address contains invalid characters'
        }
    },
    address_line_2: {
        required: false,
        minLength: 3,
        maxLength: 200,
        pattern: /^[a-zA-Z0-9\s.,'#/-]+$/,
        messages: {
            minLength: 'Address line 2 must be at least 3 characters',
            maxLength: 'Address line 2 cannot exceed 200 characters',
            pattern: 'Address contains invalid characters'
        }
    }
};

// Validate individual field
function validateField(fieldId) {
    const field = $('#' + fieldId);
    if (!field.length) return true;
    
    const value = String(field.val() || '').trim();
    const rules = VALIDATION_RULES[fieldId];
    
    if (!rules) return true;
    
    let isValid = true;
    let errorMessage = '';
    
    // Required validation
    if (rules.required && !value) {
        isValid = false;
        errorMessage = rules.messages.required;
    }
    
    // Only validate other rules if field has value
    if (isValid && value) {
        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
            isValid = false;
            errorMessage = rules.messages.minLength;
        }
        
        // Max length validation
        if (isValid && rules.maxLength && value.length > rules.maxLength) {
            isValid = false;
            errorMessage = rules.messages.maxLength;
        }
        
        // Pattern validation
        if (isValid && rules.pattern && !rules.pattern.test(value)) {
            isValid = false;
            errorMessage = rules.messages.pattern;
        }
    }
    
    // Update UI
    if (!isValid) {
        field.addClass('is-invalid');
        field.removeClass('is-valid');
        field.siblings('.invalid-feedback').first().text(errorMessage);
        field.siblings('.valid-feedback').first().text('');
    } else if (value || rules.required) {
        field.removeClass('is-invalid');
        field.addClass('is-valid');
        field.siblings('.invalid-feedback').first().text('');
        field.siblings('.valid-feedback').first().text('Looks good!');
    } else {
        field.removeClass('is-invalid is-valid');
        field.siblings('.invalid-feedback').first().text('');
        field.siblings('.valid-feedback').first().text('');
    }
    
    return isValid;
}

// Validate entire form
function validateForm() {
    let isFormValid = true;
    
    // Validate all fields with rules
    Object.keys(VALIDATION_RULES).forEach(function(fieldId) {
        const isValid = validateField(fieldId);
        if (!isValid) {
            isFormValid = false;
        }
    });
    
    // Enable/disable save button
    $('#addressSaveBtn').prop('disabled', !isFormValid);
    
    return isFormValid;
}

// Real-time validation on input
function setupRealtimeValidation() {
    // Text inputs - validate on blur and input (with debounce)
    $('#addressForm input[type="text"], #addressForm input[type="email"]').each(function() {
        const fieldId = $(this).attr('id');
        let typingTimer;
        const doneTypingInterval = 500; // ms
        
        // Validate on blur
        $(this).on('blur', function() {
            validateField(fieldId);
            validateForm();
        });
        
        // Validate on input (debounced)
        $(this).on('input', function() {
            clearTimeout(typingTimer);
            const self = this;
            typingTimer = setTimeout(function() {
                validateField($(self).attr('id'));
                validateForm();
            }, doneTypingInterval);
        });
    });
    
    // Select dropdowns - validate on change
    $('#addressForm select').on('change', function() {
        const fieldId = $(this).attr('id');
        validateField(fieldId);
        validateForm();
    });
    
    // Checkbox - no validation needed but trigger form validation
    $('#is_default').on('change', function() {
        validateForm();
    });
}

// Phone number formatting and validation
function setupPhoneValidation() {
    const phoneInput = $('#phone');
    
    // Format phone number as user types
    phoneInput.on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        
        // Limit to 15 digits
        if (value.length > 15) {
            value = value.substring(0, 15);
        }
        
        $(this).val(value);
        
        // Trigger validation
        setTimeout(function() {
            validateField('phone');
            validateForm();
        }, 100);
    });
    
    // Prevent non-numeric input
    phoneInput.on('keypress', function(e) {
        const charCode = e.which ? e.which : e.keyCode;
        // Allow: backspace, delete, tab, escape, enter
        if ([8, 9, 27, 13, 46].indexOf(charCode) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (charCode === 65 && e.ctrlKey === true) ||
            (charCode === 67 && e.ctrlKey === true) ||
            (charCode === 86 && e.ctrlKey === true) ||
            (charCode === 88 && e.ctrlKey === true)) {
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((charCode < 48 || charCode > 57)) {
            e.preventDefault();
        }
    });
}

// Postal code formatting
function setupPostalCodeValidation() {
    const postalInput = $('#postal_code');
    
    postalInput.on('input', function() {
        let value = $(this).val().toUpperCase(); // Convert to uppercase
        
        // Remove invalid characters
        value = value.replace(/[^0-9A-Z\s-]/g, '');
        
        // Limit to 10 characters
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        $(this).val(value);
        
        // Trigger validation
        setTimeout(function() {
            validateField('postal_code');
            validateForm();
        }, 100);
    });
}

// Function to prevent nice-select from interfering
function preventNiceSelectInterference() {
    // Override the nice-select initialization for our dropdowns
    $(document).on('DOMNodeInserted', function() {
        $('#addressModal select[data-no-nice-select]').each(function() {
            var $select = $(this);
            var $niceSelect = $select.next('.nice-select');
            if ($niceSelect.length) {
                $niceSelect.remove();
                $select.show();
            }
        });
    });
    
    // Also check when modal is shown
    $('#addressModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            $('#addressModal select[data-no-nice-select]').each(function() {
                var $select = $(this);
                var $niceSelect = $select.next('.nice-select');
                if ($niceSelect.length) {
                    $niceSelect.remove();
                    $select.show();
                }
            });
        }, 50);
    });
}

// Load phone codes
function loadPhoneCodes() {
    console.log('[AddressModal] Loading phone codes...');
    
    $.ajax({
        url: '{{ route("locations.phonecodes") }}',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] Phone codes loaded:', data);
            const select = $('#country_code');
            select.empty().append('<option value="">Select Code</option>');
            
            if (Array.isArray(data)) {
                data.forEach(code => {
                    const flag = getFlagEmoji(code.iso_code);
                    select.append(`<option value="${code.phone_code}">${flag} ${code.phone_code}</option>`);
                });
                // Enable the dropdown
                select.prop('disabled', false);
                select.removeClass('disabled is-invalid is-valid');
                console.log('[AddressModal] Phone codes dropdown enabled');
            }
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading phone codes:', {xhr, status, error});
            $('#country_code').empty().append('<option value="">Error loading codes</option>');
        }
    });
}

// Load countries
function loadCountries() {
    console.log('[AddressModal] Loading countries...');
    
    $.ajax({
        url: '{{ route("locations.countries") }}',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] Countries loaded:', data);
            const select = $('#country');
            select.empty().append('<option value="">Select Country</option>');
            
            if (Array.isArray(data)) {
                data.forEach(country => {
                    const flag = getFlagEmoji(country.iso_code);
                    select.append(`<option value="${country.id}">${flag} ${country.name}</option>`);
                });
                // Enable the dropdown
                select.prop('disabled', false);
                select.removeClass('disabled is-invalid is-valid');
                console.log('[AddressModal] Countries dropdown enabled');
            }
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading countries:', {xhr, status, error});
            $('#country').empty().append('<option value="">Error loading countries</option>');
        }
    });
}

// Load states for a country
function loadStates(countryId) {
    console.log('[AddressModal] Loading states for country:', countryId);
    
    // Reset and disable dependent dropdowns
    $('#state').empty().append('<option value="">Loading...</option>').prop('disabled', true);
    $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled');
    
    $.ajax({
        url: '{{ route("locations.states", ["country" => "_id_"]) }}'.replace('_id_', countryId),
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] States loaded:', data);
            const select = $('#state');
            select.empty().append('<option value="">Select State</option>');
            
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(state => {
                    select.append(`<option value="${state.id}">${state.name}</option>`);
                });
                select.prop('disabled', false);
                select.removeClass('disabled is-invalid is-valid');
                console.log('[AddressModal] States dropdown enabled');
            } else {
                select.append('<option value="">No states available</option>');
                select.prop('disabled', true);
            }
            
            validateField('state');
            validateForm();
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading states:', {xhr, status, error});
            $('#state').empty().append('<option value="">Error loading states</option>').prop('disabled', true).addClass('disabled');
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled');
        }
    });
}

// Load cities for a state
function loadCities(stateId) {
    console.log('[AddressModal] Loading cities for state:', stateId);
    
    // Reset and show loading
    $('#city').empty().append('<option value="">Loading...</option>').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("locations.cities", ["state" => "_id_"]) }}'.replace('_id_', stateId),
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] Cities loaded:', data);
            const select = $('#city');
            select.empty().append('<option value="">Select City</option>');
            
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(city => {
                    select.append(`<option value="${city.id}">${city.name}</option>`);
                });
                select.prop('disabled', false);
                select.removeClass('disabled is-invalid is-valid');
                console.log('[AddressModal] Cities dropdown enabled');
            } else {
                select.append('<option value="">No cities available</option>');
                select.prop('disabled', true);
            }
            
            validateField('city');
            validateForm();
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading cities:', {xhr, status, error});
            $('#city').empty().append('<option value="">Error loading cities</option>').prop('disabled', true).addClass('disabled');
        }
    });
}

// Get flag emoji for country code
function getFlagEmoji(isoCode) {
    if (!isoCode) return '';
    try {
        return isoCode.toUpperCase()
            .split('')
            .map(char => 127397 + char.charCodeAt())
            .map(code => String.fromCodePoint(code))
            .join('');
    } catch (e) {
        return '';
    }
}

// Bind event handlers
function bindEventHandlers() {
    // Country change handler
    $('#country').on('change', function() {
        const countryId = $(this).val();
        console.log('[AddressModal] Country changed:', countryId);
        
        // Clear state and city
        $('#state').empty().append('<option value="">Select State</option>').prop('disabled', true).addClass('disabled').removeClass('is-valid is-invalid');
        $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled').removeClass('is-valid is-invalid');
        
        if (!countryId) {
            validateField('country');
            validateForm();
            return;
        }
        
        loadStates(countryId);
        validateField('country');
    });
    
    // State change handler
    $('#state').on('change', function() {
        const stateId = $(this).val();
        console.log('[AddressModal] State changed:', stateId);
        
        // Clear city
        $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled').removeClass('is-valid is-invalid');
        
        if (!stateId) {
            validateField('state');
            validateForm();
            return;
        }
        
        loadCities(stateId);
        validateField('state');
    });
    
    // Setup real-time validation
    setupRealtimeValidation();
    setupPhoneValidation();
    setupPostalCodeValidation();
}

// Global function to open address modal
window.openAddressModal = function(id) {
    console.log('[AddressModal] Opening address modal, id:', id);
    
    const modal = $('#addressModal');
    
    if (id) {
        // Edit mode
        $('#addressModalLabel').text('Edit Address');
        $('#addressMethod').val('PUT');
        $('#addressId').val(id);
        
        // Load address data for editing
        loadAddressForEdit(id);
    } else {
        // Add mode
        $('#addressModalLabel').text('Add New Address');
        $('#addressMethod').val('POST');
        $('#addressId').val('');
        resetAddressForm();
    }
    
    modal.modal('show');
    
    // Ensure nice-select doesn't interfere with our dropdowns
    setTimeout(function() {
        // Remove any nice-select wrappers that might have been applied
        $('#addressModal select[data-no-nice-select]').each(function() {
            var $select = $(this);
            var $niceSelect = $select.next('.nice-select');
            if ($niceSelect.length) {
                $niceSelect.remove();
                $select.show();
            }
        });
        
        // Re-enable dropdowns if they were disabled
        $('#addressModal select[data-no-nice-select]').prop('disabled', false);
        
        console.log('[AddressModal] Ensured native dropdowns are working');
    }, 100);
};

// Load address data for editing
function loadAddressForEdit(id) {
    // Show loading state
    $('#addressSaveBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    
    $.ajax({
        url: `/addresses/${id}/edit`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.address) {
                fillAddressForm(response.address);
            } else {
                alert('Failed to load address data');
            }
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading address for edit:', {xhr, status, error});
            alert('Failed to load address data');
        },
        complete: function() {
            $('#addressSaveBtn').html('<i class="fa fa-save"></i> Save Address');
        }
    });
}

// Fill form with address data
function fillAddressForm(address) {
    // Clear all validation states first
    $('#addressForm .form-control').removeClass('is-valid is-invalid');
    $('#addressForm .invalid-feedback').text('');
    $('#addressForm .valid-feedback').text('');
    
    // Split name if first/last not provided
    var first = address.first_name || '';
    var last = address.last_name || '';
    if ((!first || !last) && address.full_name) {
        var parts = String(address.full_name).trim().split(/\s+/);
        if (parts.length === 1) { 
            first = parts[0]; 
        } else if (parts.length >= 2) { 
            first = parts.shift(); 
            last = parts.join(' '); 
        }
    }
    
    $('#first_name').val(first);
    $('#last_name').val(last);
    $('#country_code').val(address.country_code || '');
    $('#phone').val(address.phone || '');
    
    // Email fallback
    if (address.email) {
        $('#email').val(address.email);
    } else if (window.ADDRESS_MODAL_USER && window.ADDRESS_MODAL_USER.email) {
        $('#email').val(window.ADDRESS_MODAL_USER.email);
    } else {
        $('#email').val('');
    }
    
    $('#postal_code').val(address.postal_code || '');
    $('#address_line_1').val(address.address_line_1 || '');
    $('#address_line_2').val(address.address_line_2 || '');
    $('#company').val(address.company || '');
    $('#is_default').prop('checked', address.is_default || false);
    
    // Set country and load states
    if (address.country_id) {
        $('#country').val(address.country_id);
        loadStates(address.country_id);
        
        // Set state and load cities after states are loaded
        setTimeout(function() {
            if (address.state_id) {
                $('#state').val(address.state_id);
                loadCities(address.state_id);
                
                // Set city after cities are loaded
                setTimeout(function() {
                    if (address.city_id) {
                        $('#city').val(address.city_id);
                        validateField('city');
                    }
                    
                    // Validate all fields after loading
                    setTimeout(function() {
                        validateForm();
                    }, 200);
                }, 600);
            }
        }, 600);
    }
    
    // Validate text fields immediately
    setTimeout(function() {
        ['first_name', 'last_name', 'company', 'email', 'phone', 'postal_code', 'address_line_1', 'address_line_2', 'country_code'].forEach(function(fieldId) {
            validateField(fieldId);
        });
    }, 100);
}

// Reset form
function resetAddressForm() {
    $('#addressForm')[0].reset();
    
    // Clear all validation states
    $('#addressForm .form-control, #addressForm select').removeClass('is-valid is-invalid');
    $('#addressForm .invalid-feedback').text('');
    $('#addressForm .valid-feedback').text('');
    
    // Reset dropdowns
    $('#state, #city').empty().append('<option value="">Select State</option>').prop('disabled', true).addClass('disabled');
    $('#addressSaveBtn').prop('disabled', true);
    
    // Reload initial data
    loadPhoneCodes();
    loadCountries();

    // Autofill from current user
    try {
        var fullName = (window.ADDRESS_MODAL_USER && window.ADDRESS_MODAL_USER.name) ? String(window.ADDRESS_MODAL_USER.name) : '';
        var email = (window.ADDRESS_MODAL_USER && window.ADDRESS_MODAL_USER.email) ? String(window.ADDRESS_MODAL_USER.email) : '';
        var first = '', last = '';
        if (fullName) {
            var parts = fullName.trim().split(/\s+/);
            if (parts.length === 1) { 
                first = parts[0]; 
            } else if (parts.length >= 2) { 
                first = parts.shift(); 
                last = parts.join(' '); 
            }
        }
        $('#first_name').val(first);
        $('#last_name').val(last);
        $('#email').val(email);
        
        // Validate pre-filled fields
        setTimeout(function() {
            if (first) validateField('first_name');
            if (last) validateField('last_name');
            if (email) validateField('email');
            validateForm();
        }, 100);
    } catch (e) {
        console.error('[AddressModal] Error autofilling user data:', e);
    }
}

// Global save address function
window.saveAddress = function() {
    console.log('[AddressModal] Save address clicked');
    
    // Validate form before submission
    if (!validateForm()) {
        console.log('[AddressModal] Form validation failed');
        
        // Scroll to first invalid field
        const firstInvalid = $('#addressForm .is-invalid').first();
        if (firstInvalid.length) {
            firstInvalid.focus();
            $('#addressModal .modal-body').animate({
                scrollTop: firstInvalid.offset().top - $('#addressModal .modal-body').offset().top + $('#addressModal .modal-body').scrollTop() - 100
            }, 300);
        }
        
        return;
    }
    
    const form = $('#addressForm');
    const formData = new FormData(form[0]);
    const method = $('#addressMethod').val();
    const addressId = $('#addressId').val();
    
    let url = '/addresses';
    if (method === 'PUT' && addressId) {
        url = `/addresses/${addressId}`;
        formData.append('_method', 'PUT');
    }
    
    // Show loading state
    $('#addressSpinner').removeClass('d-none');
    $('#addressSaveBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('[AddressModal] Address saved successfully:', response);
            $('#addressModal').modal('hide');
            
            // Reload page to show new/updated address
            setTimeout(function() {
                location.reload();
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error saving address:', {xhr, status, error});
            
            // Handle validation errors
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                
                // Display server-side validation errors
                Object.keys(errors).forEach(function(fieldName) {
                    const fieldId = fieldName;
                    const field = $('#' + fieldId);
                    
                    if (field.length) {
                        field.addClass('is-invalid');
                        field.removeClass('is-valid');
                        field.siblings('.invalid-feedback').first().text(errors[fieldName][0]);
                    }
                });
                
                // Scroll to first error
                const firstInvalid = $('#addressForm .is-invalid').first();
                if (firstInvalid.length) {
                    firstInvalid.focus();
                    $('#addressModal .modal-body').animate({
                        scrollTop: firstInvalid.offset().top - $('#addressModal .modal-body').offset().top + $('#addressModal .modal-body').scrollTop() - 100
                    }, 300);
                }
            } else {
                const message = xhr.responseJSON?.message || 'Error saving address. Please try again.';
                alert(message);
            }
        },
        complete: function() {
            $('#addressSpinner').addClass('d-none');
            $('#addressSaveBtn').prop('disabled', false).html('<i class="fa fa-save"></i> Save Address');
        }
    });
};
</script>

<style>
/* Force dropdown styling to work properly and override nice-select */
.address-dropdown {
    appearance: auto !important;
    -webkit-appearance: menulist !important;
    -moz-appearance: menulist !important;
    cursor: pointer !important;
    background-color: #fff !important;
    border: 1px solid #ced4da !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    color: #495057 !important;
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23666' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 16px 12px !important;
    padding-right: 2.25rem !important;
    display: block !important;
    width: 100% !important;
    height: auto !important;
    position: relative !important;
    z-index: 1 !important;
}

/* Override any nice-select styling that might interfere */
#addressModal .nice-select {
    display: none !important;
}

#addressModal select[data-no-nice-select] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.address-dropdown:focus {
    border-color: #e7ab3c !important;
    box-shadow: 0 0 0 0.2rem rgba(231, 171, 60, 0.25) !important;
    outline: none !important;
}

.address-dropdown:not([disabled]) {
    cursor: pointer !important;
    background-color: #fff !important;
}

.address-dropdown[disabled] {
    cursor: not-allowed !important;
    background-color: #e9ecef !important;
    opacity: 0.65 !important;
}

/* Modal styling */
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
</style>