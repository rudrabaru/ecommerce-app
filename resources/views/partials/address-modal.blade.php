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
                                <select class="form-control" id="country_code" name="country_code" style="max-width: 80px;">
                                    <option value="">Select</option>
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
                                        id="country" name="country_id" required>
                                    <option value="">Select Country</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State <span class="text-danger">*</span></label>
                                <select class="form-control @error('state') is-invalid @enderror" 
                                        id="state" name="state_id" required disabled>
                                    <option value="">Select State</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City <span class="text-danger">*</span></label>
                                <select class="form-control @error('city') is-invalid @enderror" 
                                        id="city" name="city_id" required disabled>
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

<!-- Debug Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking jQuery');
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
    } else {
        console.log('jQuery version:', jQuery.fn.jquery);
        
        // Test API endpoints
        console.log('Testing API endpoints...');
        
        $.ajax({
            url: '            url: '{{ route("locations.phonecodes") }}',',
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                console.log('Phone codes loaded:', data);
                const select = $('#country_code');
                select.empty();
                data.forEach(code => {
                    select.append(`<option value="${code.phone_code}">${getFlagEmoji(code.iso_code)} ${code.phone_code}</option>`);
                });
            },
            error: function(xhr, status, error) {
                console.error('Phone codes error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseText,
                    error: error
                });
            }
        });

        $.ajax({
            url: '{{ route("locations.countries") }}',
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                console.log('Countries loaded:', data);
                const select = $('#country');
                select.empty();
                select.append('<option value="">Select Country</option>');
                data.forEach(country => {
                    select.append(`<option value="${country.id}">${getFlagEmoji(country.iso_code)} ${country.name}</option>`);
                });
            },
            error: function(xhr, status, error) {
                console.error('Countries error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseText,
                    error: error
                });
                $('#country').empty().append('<option value="">Error loading countries</option>');
            }
        });

        // Handle country change
        $('#country').on('change', function() {
            const countryId = $(this).val();
            if (!countryId) {
                $('#state').empty().append('<option value="">Select State</option>').prop('disabled', true);
                $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
                return;
            }

            $.ajax({
                url: '{{ route("locations.states", ["country" => "_id_"]) }}'.replace('_id_', countryId),
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    const select = $('#state');
                    select.empty();
                    select.append('<option value="">Select State</option>');
                    data.forEach(state => {
                        select.append(`<option value="${state.id}">${state.name}</option>`);
                    });
                    select.prop('disabled', false);
                    $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
                },
                error: function(xhr, status, error) {
                    console.error('States error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        response: xhr.responseText,
                        error: error
                    });
                    $('#state').empty().append('<option value="">Error loading states</option>').prop('disabled', true);
                    $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
                }
            });
        });

        // Handle state change
        $('#state').on('change', function() {
            const stateId = $(this).val();
            if (!stateId) {
                $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
                return;
            }

            $.ajax({
                url: '{{ route("locations.cities", ["state" => "_id_"]) }}'.replace('_id_', stateId),
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    const select = $('#city');
                    select.empty();
                    select.append('<option value="">Select City</option>');
                    data.forEach(city => {
                        select.append(`<option value="${city.id}">${city.name}</option>`);
                    });
                    select.prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('Cities error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        response: xhr.responseText,
                        error: error
                    });
                    $('#city').empty().append('<option value="">Error loading cities</option>').prop('disabled', true);
                }
            });
        });

        // Add getFlagEmoji function if not already defined
        if (typeof getFlagEmoji === 'undefined') {
            window.getFlagEmoji = function(isoCode) {
                const codePoints = isoCode
                    .toUpperCase()
                    .split('')
                    .map(char => 127397 + char.charCodeAt());
                return String.fromCodePoint(...codePoints);
            };
        }
    }
});
</script>

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

@include('partials.address-modal-script')
