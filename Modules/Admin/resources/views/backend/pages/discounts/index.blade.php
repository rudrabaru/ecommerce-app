<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" id="createDiscountBtn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Discount Code
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="discounts-table" class="table table-hover" width="100%"
                           data-dt-url="{{ route('admin.discounts.data') }}"
                           data-dt-page-length="25"
                           data-dt-order='[[0, "desc"]]'>
                        <thead class="table-light">
                        <tr>
                            <th data-column="id" data-width="60px">ID</th>
                            <th data-column="code">Code</th>
                            <th data-column="discount_type">Type</th>
                            <th data-column="discount_value">Value</th>
                            <th data-column="is_active">Status</th>
                            <th data-column="categories" data-orderable="false" data-searchable="false">Categories</th>
                            <th data-column="valid_from">Valid From</th>
                            <th data-column="valid_until">Valid Until</th>
                            <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin::backend.pages.discounts.modal')

    @push('scripts')
    <script>
    (function(){
        'use strict';
        
        // Prevent multiple initializations
        if (window.DiscountModalInitialized) {
            console.log('[Discount] Handlers already initialized, skipping...');
            return;
        }
        window.DiscountModalInitialized = true;
        console.log('[Discount] Initializing handlers...');

        var $ = window.jQuery;
        if (!$) {
            console.error('[Discount] jQuery not available');
            return;
        }

        // Helper: fetch categories from server
        function fetchCategories() {
            return $.ajax({
                url: '/admin/discount-codes/create',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: false
            }).then(function(response) {
                return response.categories || [];
            }).catch(function(err) {
                console.error('[Discount] Failed to fetch categories:', err);
                if (window.Swal) Swal.fire('Error', 'Could not load categories.', 'error');
                return [];
            });
        }

        // Helper: build a category select element
        function buildCategorySelect(categories, selectedValue) {
            var $select = $('<select name="category_ids[]" class="form-select category-select" required></select>');
            $select.append('<option value="">Select Category</option>');
            
            $.each(categories, function(i, cat) {
                var $option = $('<option></option>').val(cat.id).text(cat.name);
                if (selectedValue && String(selectedValue) === String(cat.id)) {
                    $option.prop('selected', true);
                }
                $select.append($option);
            });
            
            return $select;
        }

        // Helper: update visibility of remove buttons
        function updateRemoveButtons($container) {
            var rowCount = $container.find('.category-row').length;
            $container.find('.remove-category').each(function() {
                $(this).toggle(rowCount > 1);
            });
        }

        // Helper: populate categories container
        function populateCategoriesContainer($container, selectedCategories) {
            return fetchCategories().then(function(categories) {
                $container.empty();
                
                if (selectedCategories && selectedCategories.length > 0) {
                    // Edit mode - create row for each selected category
                    $.each(selectedCategories, function(i, category) {
                        var $row = $('<div class="category-row mb-2"></div>');
                        var $flexDiv = $('<div class="d-flex gap-2"></div>');
                        var $select = buildCategorySelect(categories, category.id);
                        var $removeBtn = $('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                        
                        $flexDiv.append($select).append($removeBtn);
                        $row.append($flexDiv).append('<div class="invalid-feedback"></div>');
                        $container.append($row);
                    });
                } else {
                    // Create mode - single empty row
                    var $row = $('<div class="category-row mb-2"></div>');
                    var $flexDiv = $('<div class="d-flex gap-2"></div>');
                    var $select = buildCategorySelect(categories, null);
                    var $removeBtn = $('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                    
                    $flexDiv.append($select).append($removeBtn);
                    $row.append($flexDiv).append('<div class="invalid-feedback"></div>');
                    $container.append($row);
                }
                
                updateRemoveButtons($container);
            });
        }

        // Helper: reset and prepare form for create/edit
        function resetForm($form, mode, discountId) {
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            
            if (mode === 'edit' && discountId) {
                $('#discountMethod').val('PUT');
                $('#discountId').val(discountId);
                $form[0].action = '/admin/discount-codes/' + discountId;
                $('#discountModalLabel').text('Edit Discount');
            } else {
                $('#discountMethod').val('POST');
                $('#discountId').val('');
                $form[0].action = '/admin/discount-codes';
                $('#discountModalLabel').text('Create Discount');
            }
        }

        // Helper: load discount data for edit
        function loadDiscountData(discountId, $form, $container) {
            return $.ajax({
                url: '/admin/discount-codes/' + discountId + '/edit',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: false
            }).then(function(data) {
                var d = data.discount;
                
                // Fill form fields
                $('#code').val(d.code || '');
                $('#discount_type').val(d.discount_type || 'fixed');
                $('#discount_value').val(d.discount_value || '');
                $('#minimum_order_amount').val(d.minimum_order_amount || '');
                $('#usage_limit').val(d.usage_limit || '');
                $('#is_active').val(d.is_active ? '1' : '0');
                
                // Format datetime fields
                if (d.valid_from) {
                    var validFrom = String(d.valid_from).replace(' ', 'T').slice(0, 16);
                    $('#valid_from').val(validFrom);
                }
                if (d.valid_until) {
                    var validUntil = String(d.valid_until).replace(' ', 'T').slice(0, 16);
                    $('#valid_until').val(validUntil);
                }
                
                // Populate categories
                return populateCategoriesContainer($container, d.categories || []);
            }).catch(function(err) {
                console.error('[Discount] Failed to load discount data:', err);
                if (window.Swal) Swal.fire('Error', 'Failed to load discount data.', 'error');
                throw err;
            });
        }

        // Create button handler (uses event delegation on body)
        $(document).off('click.discountCreate').on('click.discountCreate', '#createDiscountBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[Discount] Create button clicked');
            
            var $form = $('#discountForm');
            var $container = $('#categoriesContainer');
            var modal = document.getElementById('discountModal');
            
            if (!modal || !$form.length) {
                console.error('[Discount] Modal or form not found');
                return;
            }
            
            resetForm($form, 'create', null);
            
            // Populate categories then show modal
            populateCategoriesContainer($container, []).then(function() {
                var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
                bsModal.show();
            });
        });

        // Edit button handler (uses event delegation on document)
        $(document).off('click.discountEdit').on('click.discountEdit', '.js-discount-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(this);
            var discountId = $btn.data('discount-id') || $btn.data('id');
            console.log('[Discount] Edit button clicked, ID:', discountId);
            
            var $form = $('#discountForm');
            var $container = $('#categoriesContainer');
            var modal = document.getElementById('discountModal');
            
            if (!modal || !$form.length) {
                console.error('[Discount] Modal or form not found');
                return;
            }
            
            if (!discountId) {
                console.error('[Discount] No discount ID found');
                return;
            }
            
            resetForm($form, 'edit', discountId);
            
            // Load data then show modal
            loadDiscountData(discountId, $form, $container).then(function() {
                var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
                bsModal.show();
            });
        });

        // Add category button handler
        $(document).off('click.addCategory').on('click.addCategory', '#addCategoryBtn', function(e) {
            e.preventDefault();
            console.log('[Discount] Add category button clicked');
            
            var $container = $('#categoriesContainer');
            
            fetchCategories().then(function(categories) {
                var $row = $('<div class="category-row mb-2"></div>');
                var $flexDiv = $('<div class="d-flex gap-2"></div>');
                var $select = buildCategorySelect(categories, null);
                var $removeBtn = $('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                
                $flexDiv.append($select).append($removeBtn);
                $row.append($flexDiv).append('<div class="invalid-feedback"></div>');
                $container.append($row);
                
                updateRemoveButtons($container);
            });
        });

        // Remove category button handler (uses delegation on container)
        $(document).off('click.removeCategory').on('click.removeCategory', '#categoriesContainer .remove-category', function(e) {
            e.preventDefault();
            console.log('[Discount] Remove category button clicked');
            
            var $btn = $(this);
            var $row = $btn.closest('.category-row');
            var $container = $('#categoriesContainer');
            
            // Ensure at least one row remains
            if ($container.find('.category-row').length > 1) {
                $row.remove();
                updateRemoveButtons($container);
            }
        });

        // Form input validation handler
        $(document).off('input.discountValidation change.discountValidation')
            .on('input.discountValidation change.discountValidation', '#discountForm input, #discountForm select', function(e) {
            
            // Uppercase code field
            if (this.id === 'code') {
                this.value = this.value.toUpperCase();
            }
            
            // Remove validation errors on input
            var $input = $(this);
            if ($input.hasClass('is-invalid')) {
                $input.removeClass('is-invalid');
                $input.siblings('.invalid-feedback').text('');
            }
        });

        // Form submission handler
        $(document).off('submit.discountForm').on('submit.discountForm', '#discountForm', function(e) {
            e.preventDefault();
            console.log('[Discount] Form submitted');
            
            var form = this;
            var $form = $(form);
            var formData = new FormData(form);
            var url = form.action;
            var $saveBtn = $('#discountSaveBtn');
            var $spinner = $('#discountSpinner');
            
            // Show loading state
            $saveBtn.prop('disabled', true);
            $spinner.removeClass('d-none');
            
            // Clear previous errors
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('[Discount] Form response:', data);
                
                if (data.success) {
                    // Close modal
                    var modal = document.getElementById('discountModal');
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    
                    // Reload DataTable
                    if (window.DataTableInstances && window.DataTableInstances['discounts-table']) {
                        window.DataTableInstances['discounts-table'].ajax.reload(null, false);
                    }
                    
                    // Show success message
                    if (window.Swal) {
                        Swal.fire('Success', data.message || 'Discount code saved successfully!', 'success');
                    }
                } else {
                    // Show validation errors
                    if (data.errors) {
                        $.each(data.errors, function(field, messages) {
                            var $input = $('[name="' + field + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            }
                        });
                    }
                    
                    if (window.Swal) {
                        Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
                    }
                }
            })
            .catch(function(err) {
                console.error('[Discount] Form submission error:', err);
                if (window.Swal) {
                    Swal.fire('Error', 'An error occurred while saving.', 'error');
                }
            })
            .finally(function() {
                $saveBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            });
        });

        // Global save function for modal button
        window.saveDiscount = function() {
            console.log('[Discount] Save button clicked');
            $('#discountForm').trigger('submit');
        };

        console.log('[Discount] Handlers initialized successfully');
    })();
    </script>
    @endpush
</x-app-layout>