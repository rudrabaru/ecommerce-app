<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" class="btn btn-primary" data-action="create" data-bs-toggle="modal" data-bs-target="#discountModal">
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
        
        var $ = window.jQuery;
        if (!$) {
            console.error('[Discount] jQuery not available');
            return;
        }
        
        // Ensure function is available on window immediately
        window.openDiscountModal = window.openDiscountModal || function() {};

        // Helper: fetch categories from server
        function fetchCategories() {
            return $.ajax({
                url: '/admin/discount-codes/create',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: false
            }).then(function(response) {
                return response.categories || [];
            }).catch(function() {
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

        // Helper: populate categories container - ALWAYS shows at least one dropdown
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
                    // Create mode - ALWAYS show at least one empty row
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

        // Single modal function for both create and edit (reassign to ensure it's available)
        window.openDiscountModal = function(discountId = null) {
            var $form = $('#discountForm');
            var $container = $('#categoriesContainer');
            
            // Reset form
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            
            if (discountId) {
                // Edit mode
                $('#discountModalLabel').text('Edit Discount');
                $('#discountMethod').val('PUT');
                $('#discountId').val(discountId);
                $form[0].action = '/admin/discount-codes/' + discountId;
                
                // Load discount data
                $.ajax({
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
                    
                    // Populate categories with existing data
                    populateCategoriesContainer($container, d.categories || []);
                }).catch(function() {
                    if (window.Swal) Swal.fire('Error', 'Failed to load discount data.', 'error');
                });
            } else {
                // Create mode
                $('#discountModalLabel').text('Create Discount');
                $('#discountMethod').val('POST');
                $('#discountId').val('');
                $form[0].action = '/admin/discount-codes';
                
                // Always show at least one category dropdown
                populateCategoriesContainer($container, []);
            }
        };

        // Initialize modal behavior
        document.addEventListener('DOMContentLoaded', function() {
            const discountModal = document.getElementById('discountModal');
            if (discountModal) {
                discountModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (button) {
                        if (button.dataset.action === 'create') {
                            openDiscountModal(null);
                        } else {
                            const discountId = button.getAttribute('data-id');
                            if (discountId) {
                                openDiscountModal(discountId);
                            }
                        }
                    }
                });
            }
        });

        // Re-initialize on AJAX page load
        window.addEventListener('ajaxPageLoaded', function() {
            const discountModal = document.getElementById('discountModal');
            if (discountModal) {
                discountModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (button) {
                        if (button.dataset.action === 'create') {
                            openDiscountModal(null);
                        } else {
                            const discountId = button.getAttribute('data-id');
                            if (discountId) {
                                openDiscountModal(discountId);
                            }
                        }
                    }
                });
            }
        });

        // Add category button handler
        $(document).off('click.addCategory').on('click.addCategory', '#addCategoryBtn', function(e) {
            e.preventDefault();
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

        // Remove category button handler
        $(document).off('click.removeCategory').on('click.removeCategory', '#categoriesContainer .remove-category', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.category-row');
            var $container = $('#categoriesContainer');
            
            // Ensure at least one row remains
            if ($container.find('.category-row').length > 1) {
                $row.remove();
                updateRemoveButtons($container);
            }
        });

        // Form input validation handler - uppercase code
        $(document).off('input.discountValidation').on('input.discountValidation', '#discountForm #code', function() {
            this.value = this.value.toUpperCase();
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('');
        });

        // Save function
        window.saveDiscount = function() {
            var $form = $('#discountForm');
            var formData = new FormData($form[0]);
            var url = $form[0].action;
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
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('discountModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable using global function
                    window.reloadDataTable('discounts-table');
                    
                    // Show success message
                    if (window.Swal) {
                        Swal.fire('Success', data.message || 'Discount code saved!', 'success');
                    } else {
                        alert(data.message || 'Discount code saved!');
                    }
                } else {
                    // Show validation errors
                    if (data.errors) {
                        $.each(data.errors, function(field, messages) {
                            var $input = $form.find('[name="' + field + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            }
                        });
                    }
                    
                    if (window.Swal) {
                        Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
                    } else {
                        alert(data.message || 'Please fix the errors above.');
                    }
                }
            })
            .catch(function() {
                if (window.Swal) {
                    Swal.fire('Error', 'An error occurred while saving.', 'error');
                } else {
                    alert('An error occurred while saving.');
                }
            })
            .finally(function() {
                $saveBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            });
        };

    })();
    </script>
    @endpush
</x-app-layout>
