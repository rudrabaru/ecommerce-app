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
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
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
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country">Country <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="country" name="country_id" required data-no-nice-select="1">
                                    <option value="">Select Country</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="state" name="state_id" required disabled data-no-nice-select="1">
                                    <option value="">Select State</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City <span class="text-danger">*</span></label>
                                <select class="form-control address-dropdown" id="city" name="city_id" required disabled data-no-nice-select="1">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Pin Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="eg. 900001" required>
                    </div>

                    <div class="form-group">
                        <label for="address_line_1">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address_line_1" name="address_line_1" 
                               placeholder="eg. 123 Elm Street, Springfield" required>
                    </div>

                    <div class="form-group">
                        <label for="address_line_2">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line_2" name="address_line_2" 
                               placeholder="Apartment, suite, unit, etc. (optional)">
                    </div>

                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" class="form-control" id="company" name="company" placeholder="Company (optional)">
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
                select.removeClass('disabled');
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
                select.removeClass('disabled');
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
    
    $.ajax({
        url: '{{ route("locations.states", ["country" => "_id_"]) }}'.replace('_id_', countryId),
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] States loaded:', data);
            const select = $('#state');
            select.empty().append('<option value="">Select State</option>');
            
            if (Array.isArray(data)) {
                data.forEach(state => {
                    select.append(`<option value="${state.id}">${state.name}</option>`);
                });
                select.prop('disabled', false);
                select.removeClass('disabled');
                console.log('[AddressModal] States dropdown enabled');
            }
            
            // Reset city dropdown
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled');
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
    
    $.ajax({
        url: '{{ route("locations.cities", ["state" => "_id_"]) }}'.replace('_id_', stateId),
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('[AddressModal] Cities loaded:', data);
            const select = $('#city');
            select.empty().append('<option value="">Select City</option>');
            
            if (Array.isArray(data)) {
                data.forEach(city => {
                    select.append(`<option value="${city.id}">${city.name}</option>`);
                });
                select.prop('disabled', false);
                select.removeClass('disabled');
                console.log('[AddressModal] Cities dropdown enabled');
            }
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
        
        if (!countryId) {
            $('#state').empty().append('<option value="">Select State</option>').prop('disabled', true).addClass('disabled');
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled');
            return;
        }
        
        loadStates(countryId);
    });
    
    // State change handler
    $('#state').on('change', function() {
        const stateId = $(this).val();
        console.log('[AddressModal] State changed:', stateId);
        
        if (!stateId) {
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true).addClass('disabled');
            return;
        }
        
        loadCities(stateId);
    });
    
    // Form validation
    $('#addressForm').on('input change', function() {
        validateForm();
    });
}

// Validate form and enable/disable save button
function validateForm() {
    const requiredFields = ['first_name', 'last_name', 'phone', 'country_id', 'state_id', 'city_id', 'postal_code', 'address_line_1'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const value = $(`#${field}`).val();
        if (!value || value.trim() === '') {
            isValid = false;
        }
    });
    
    // Check if country code is selected
    const countryCode = $('#country_code').val();
    if (!countryCode) {
        isValid = false;
    }
    
    $('#addressSaveBtn').prop('disabled', !isValid);
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
    $.ajax({
        url: `/addresses/${id}/edit`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.address) {
                fillAddressForm(response.address);
            }
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error loading address for edit:', {xhr, status, error});
            alert('Failed to load address data');
        }
    });
}

// Fill form with address data
function fillAddressForm(address) {
    $('#first_name').val(address.first_name || '');
    $('#last_name').val(address.last_name || '');
    $('#country_code').val(address.country_code || '');
    $('#phone').val(address.phone || '');
    $('#email').val(address.email || '');
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
        setTimeout(() => {
            if (address.state_id) {
                $('#state').val(address.state_id);
                loadCities(address.state_id);
                
                // Set city after cities are loaded
                setTimeout(() => {
                    if (address.city_id) {
                        $('#city').val(address.city_id);
                    }
                }, 500);
            }
        }, 500);
    }
    
    validateForm();
}

// Reset form
function resetAddressForm() {
    $('#addressForm')[0].reset();
    $('#state, #city').prop('disabled', true).addClass('disabled');
    $('#addressSaveBtn').prop('disabled', true);
    
    // Reload initial data
    loadPhoneCodes();
    loadCountries();
}

// Global save address function
window.saveAddress = function() {
    const form = $('#addressForm');
    const formData = new FormData(form[0]);
    const method = $('#addressMethod').val();
    const addressId = $('#addressId').val();
    
    let url = '/addresses';
    if (method === 'PUT' && addressId) {
        url = `/addresses/${addressId}`;
    }
    
    // Show loading state
    $('#addressSpinner').removeClass('d-none');
    $('#addressSaveBtn').prop('disabled', true);
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('[AddressModal] Address saved successfully:', response);
            $('#addressModal').modal('hide');
            
            // Reload addresses if function exists
            if (typeof window.reloadAddresses === 'function') {
                window.reloadAddresses();
            }
            
            // Show success message
            if (response.message) {
                alert(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('[AddressModal] Error saving address:', {xhr, status, error});
            const message = xhr.responseJSON?.message || 'Error saving address';
            alert(message);
        },
        complete: function() {
            $('#addressSpinner').addClass('d-none');
            $('#addressSaveBtn').prop('disabled', false);
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