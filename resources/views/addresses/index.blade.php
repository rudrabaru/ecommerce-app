<x-header />

<x-breadcrumbs :items="[
    ['label' => 'Home', 'route' => route('home')],
    ['label' => 'My Addresses']
]" />

<!-- Addresses Section Begin -->
<section class="checkout spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="checkout__form">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Shipping Addresses</h4>
                        <button type="button" class="site-btn" onclick="openAddressModal()">
                            <i class="fa fa-plus"></i> Add New Address
                        </button>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($addresses->count() > 0)
                        <div class="row">
                            @foreach($addresses as $address)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="address-card {{ $address->is_default ? 'default-address' : '' }}">
                                        <div class="address-header">
                                            <h5>{{ $address->full_name }}</h5>
                                            @if($address->is_default)
                                                <span class="default-badge">Default</span>
                                            @endif
                                        </div>
                                        <div class="address-details">
                                            @if($address->company)
                                                <p><strong>{{ $address->company }}</strong></p>
                                            @endif
                                            <p>{{ $address->address_line_1 }}</p>
                                            @if($address->address_line_2)
                                                <p>{{ $address->address_line_2 }}</p>
                                            @endif
                                            <p>{{ optional($address->city)->name }}, {{ optional($address->state)->name }} {{ $address->postal_code }}</p>
                                            <p>{{ optional($address->country)->name }}</p>
                                            <p><strong>Phone:</strong> {{ $address->country_code }} {{ $address->phone }}</p>
                                        </div>
                                        <div class="address-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAddressModal({{ $address->id }})">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                            @if(!$address->is_default)
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="setDefaultAddress({{ $address->id }})">
                                                    <i class="fa fa-star"></i> Set Default
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAddress({{ $address->id }})">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fa fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h5>No addresses found</h5>
                                <p class="text-muted">You haven't added any shipping addresses yet.</p>
                                <button type="button" class="site-btn" onclick="openAddressModal()">
                                    <i class="fa fa-plus"></i> Add Your First Address
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Addresses Section End -->

<x-footer />

@include('partials.address-modal')

<script>
$(document).ready(function() {
    // Address modal functionality
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
                    location.reload(); // Reload to show updated addresses
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

    window.setDefaultAddress = function(id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Set as default?',
                text: 'This will become your primary shipping address.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e7ab3c',
                confirmButtonText: 'Yes, set default'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/addresses/' + id + '/set-default',
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Default updated', timer: 1200, showConfirmButton: false })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire('Error', response.message || 'Failed to set default address', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to set default address', 'error');
                        }
                    });
                }
            });
        } else {
            if (confirm('Set this address as default?')) {
                $.ajax({
                    url: '/addresses/' + id + '/set-default',
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('Failed to set default address');
                    }
                });
            }
        }
    };

    window.deleteAddress = function(id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete this address?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/addresses/' + id,
                        method: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Address deleted', timer: 1200, showConfirmButton: false })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire('Error', response.message || 'Failed to delete address', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to delete address', 'error');
                        }
                    });
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this address?')) {
                $.ajax({
                    url: '/addresses/' + id,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('Failed to delete address');
                    }
                });
            }
        }
    };

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

    // Form validation on input change
    $(document).on('input change', '#addressForm input, #addressForm select', function() {
        validateAddressForm();
    });

    // Clear validation errors when modal is closed
    $('#addressModal').on('hidden.bs.modal', function() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });
});
</script>

<style>
.address-card {
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s ease;
}

.address-card:hover {
    border-color: #e7ab3c;
    box-shadow: 0 4px 12px rgba(231, 171, 60, 0.1);
}

.default-address {
    border-color: #e7ab3c;
    background: linear-gradient(135deg, #fff9f0 0%, #ffffff 100%);
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}

.address-header h5 {
    margin: 0;
    color: #333;
}

.address-details p {
    margin-bottom: 5px;
    color: #666;
}

.address-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e5e5e5;
}

.address-actions .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}

.empty-state {
    padding: 40px 20px;
}

.default-badge {
    background: #e7ab3c;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>
