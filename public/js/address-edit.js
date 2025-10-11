// Address edit and delete functionality
$(document).ready(function() {
    // Load address for editing
    window.editAddress = function(addressId) {
        // Show modal
        const modal = $('#addressModal');
        modal.modal('show');
        
        // Show loading state
        $('#addressSaveBtn').prop('disabled', true);
        $('#addressSpinner').removeClass('d-none');
        
        // Fetch address data
        $.get(`/api/user/addresses/${addressId}`, function(response) {
            const address = response.data;
            
            // Set form method to PUT
            $('#addressMethod').val('PUT');
            
            // Fill form fields
            $('#addressId').val(address.id);
            $('#first_name').val(address.first_name);
            $('#last_name').val(address.last_name);
            $('#email').val(address.email);
            $('#phone').val(address.phone);
            $('#country_code').val(address.country_code);
            
            // Load country and select
            loadCountries().then(() => {
                $('#country').val(address.country_id);
                
                // Load states and select
                loadStates(address.country_id).then(() => {
                    $('#state').val(address.state_id);
                    
                    // Load cities and select
                    loadCities(address.state_id).then(() => {
                        $('#city').val(address.city_id);
                    });
                });
            });
            
            $('#postal_code').val(address.postal_code);
            $('#address_line_1').val(address.address_line_1);
            $('#address_line_2').val(address.address_line_2);
            $('#company').val(address.company);
            $('#is_default').prop('checked', address.is_default);
            
            // Enable save button
            $('#addressSaveBtn').prop('disabled', false);
            $('#addressSpinner').addClass('d-none');
            
            // Update modal title
            $('#addressModalLabel').text('Edit Address');
        });
    };
    
    // Delete address
    window.deleteAddress = function(addressId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/api/user/addresses/${addressId}`,
                    method: 'DELETE',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Address has been deleted.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Refresh addresses list
                        if (typeof loadAddresses === 'function') {
                            loadAddresses();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to delete address'
                        });
                    }
                });
            }
        });
    };
    
    // Convert loadCountries to Promise
    function loadCountries() {
        return new Promise((resolve, reject) => {
            $.get('/api/locations/countries', function(countries) {
                const select = $('#country');
                select.empty().append('<option value="">Select Country</option>');
                
                countries.forEach(country => {
                    select.append(`<option value="${country.id}">${country.name}</option>`);
                });
                resolve();
            }).fail(reject);
        });
    }
    
    // Convert loadStates to Promise
    function loadStates(countryId) {
        return new Promise((resolve, reject) => {
            const stateSelect = $('#state');
            const citySelect = $('#city');
            
            stateSelect.empty().append('<option value="">Select State</option>');
            citySelect.empty().append('<option value="">Select City</option>');
            
            if (!countryId) {
                resolve();
                return;
            }
            
            $.get(`/api/locations/states/${countryId}`, function(states) {
                states.forEach(state => {
                    stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                });
                resolve();
            }).fail(reject);
        });
    }
    
    // Convert loadCities to Promise
    function loadCities(stateId) {
        return new Promise((resolve, reject) => {
            const citySelect = $('#city');
            citySelect.empty().append('<option value="">Select City</option>');
            
            if (!stateId) {
                resolve();
                return;
            }
            
            $.get(`/api/locations/cities/${stateId}`, function(cities) {
                cities.forEach(city => {
                    citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                });
                resolve();
            }).fail(reject);
        });
    }
    
    // Reset form on modal show for new address
    $('#addNewAddressBtn').on('click', function() {
        const modal = $('#addressModal');
        
        // Reset form and method
        modal.find('form')[0].reset();
        $('#addressMethod').val('POST');
        $('#addressId').val('');
        
        // Update modal title
        $('#addressModalLabel').text('Add New Address');
        
        // Show modal
        modal.modal('show');
    });
});