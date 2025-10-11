<!-- Route definitions -->
<script>
window.addressRoutes = {
    phonecodes: '{{ route("locations.phonecodes") }}',
    countries: '{{ route("locations.countries") }}',
    states: '{{ route("locations.states", ["country" => "_id_"]) }}',
    cities: '{{ route("locations.cities", ["state" => "_id_"]) }}'
};
</script>

<!-- Address Modal Script -->
<script>
(function() {
    // Function to handle jQuery initialization
    function initializeWithjQuery() {
        if (typeof jQuery === 'undefined') {
            return setTimeout(initializeWithjQuery, 100);
        }

        // Use jQuery directly
        console.log('jQuery test - typeof $:', typeof jQuery);
        console.log('jQuery test - typeof jQuery:', typeof jQuery);
        console.log('jQuery version:', jQuery.fn.jquery);
        
        // Initialize the form once jQuery is definitely loaded
        initializeAddressForm();
    }

    function initializeAddressForm() {
        // Use jQuery directly
        console.log('DOM loaded, checking jQuery...');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Initializing address form dropdowns...');
        
        const $ = jQuery;
        console.log('jQuery version:', $.fn.jquery);
        console.log('Initializing address form dropdowns...');

        // Generic function to update dropdowns
        function updateDropdown(url, selectElement, placeholder, formatOption) {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log(`Data received for ${selectElement}:`, data);
                    const select = $(selectElement);
                    select.empty().append(`<option value="">${placeholder}</option>`);
                    if (Array.isArray(data)) {
                        data.forEach(item => {
                            select.append(formatOption(item));
                        });
                        select.prop('disabled', false);
                    } else {
                        console.error('Invalid data format received:', data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`Error loading ${selectElement}:`, {xhr, status, error});
                    $(selectElement).empty()
                        .append(`<option value="">Error loading ${placeholder}</option>`)
                        .prop('disabled', true);
                }
            });
        }

        // Function to get flag emoji
        function getFlagEmoji(isoCode) {
            if (!isoCode) return '';
            return isoCode.toUpperCase()
                .split('')
                .map(char => 127397 + char.charCodeAt())
                .map(code => String.fromCodePoint(code))
                .join('');
        }

        // Initial setup - disable state and city
        $('#state, #city').prop('disabled', true);

        // Load phone codes
        console.log('Loading phone codes...');
        updateDropdown(
            window.addressRoutes.phonecodes,
            '#country_code',
            'Select Code',
            code => `<option value="${code.phone_code}">${getFlagEmoji(code.iso_code)} ${code.phone_code}</option>`
        );

        // Load countries
        console.log('Loading countries...');
        updateDropdown(
            window.addressRoutes.countries,
            '#country',
            'Select Country',
            country => `<option value="${country.id}">${getFlagEmoji(country.iso_code)} ${country.name}</option>`
        );

        // Handle country change
        $('#country').on('change', function() {
            const countryId = $(this).val();
            console.log('Country changed:', countryId);
            $('#state').empty().append('<option value="">Select State</option>').prop('disabled', true);
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
            
            if (countryId) {
                updateDropdown(
                    window.addressRoutes.states.replace('_id_', countryId),
                    '#state',
                    'Select State',
                    state => `<option value="${state.id}">${state.name}</option>`
                );
            }
        });

        // Handle state change
        $('#state').on('change', function() {
            const stateId = $(this).val();
            console.log('State changed:', stateId);
            $('#city').empty().append('<option value="">Select City</option>').prop('disabled', true);
            
            if (stateId) {
                updateDropdown(
                    window.addressRoutes.cities.replace('_id_', stateId),
                    '#city',
                    'Select City',
                    city => `<option value="${city.id}">${city.name}</option>`
                );
            }
        });

        // Form submission
        $('#saveAddress').on('click', function() {
            const form = $('#addressForm');
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            const formData = new FormData(form[0]);
            $.ajax({
                url: form.attr('action') || window.location.href,
                method: form.attr('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#addressModal').modal('hide');
                        if (typeof window.reloadAddresses === 'function') {
                            window.reloadAddresses();
                        }
                    } else {
                        alert(response.message || 'Error saving address');
                    }
                },
                error: function(xhr) {
                    alert('Error saving address: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        });
    }

    // Start initialization with retry
    function tryInitialize(attempts = 0) {
        if (attempts >= 10) {
            console.error('Failed to initialize address form after multiple attempts');
            return;
        }
        
        if (typeof jQuery === 'undefined') {
            console.log('Waiting for jQuery... attempt ' + (attempts + 1));
            setTimeout(() => tryInitialize(attempts + 1), 500);
            return;
        }
        
        console.log('Starting address form initialization...');
        initializeAddressForm();
    }
    
    tryInitialize();
});
</script>