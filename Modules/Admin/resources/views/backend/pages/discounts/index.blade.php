<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" id="createDiscountBtn" class="btn btn-primary" data-action="create" data-modal="#discountCreateModal" data-bs-toggle="modal" data-bs-target="#discountCreateModal">
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
        
        // Single-pass initializer; rely on page load execution order (no duplicate guards needed)

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

        // Reset helpers for create/edit forms (separate modals)
        function resetCreateForm(){
            var $form = $('#discountCreateForm');
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $form[0].action = '/admin/discount-codes';
            populateCategoriesContainer($('#categoriesCreateContainer'), []);
        }
        function resetEditForm(){
            var $form = $('#discountEditForm');
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $('#discountEditForm input[name="_method"]').val('PUT');
            populateCategoriesContainer($('#categoriesEditContainer'), []);
        }

        // Helper: load discount data for edit
        function loadDiscountData(discountId, $form, $container) {
            return $.ajax({
                url: '/admin/discount-codes/' + discountId + '/edit',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: false
            }).then(function(data) {
                var d = data.discount;
                
                // Fill form fields in EDIT modal only
                var $f = $('#discountEditForm');
                $f[0].action = '/admin/discount-codes/' + discountId;
                $f.find('[name="code"]').val(d.code || '');
                $f.find('[name="discount_type"]').val(d.discount_type || 'fixed');
                $f.find('[name="discount_value"]').val(d.discount_value || '');
                $f.find('[name="minimum_order_amount"]').val(d.minimum_order_amount || '');
                $f.find('[name="usage_limit"]').val(d.usage_limit || '');
                $f.find('[name="is_active"]').val(d.is_active ? '1' : '0');
                
                // Format datetime fields
                if (d.valid_from) {
                    var validFrom = String(d.valid_from).replace(' ', 'T').slice(0, 16);
                    $f.find('[name="valid_from"]').val(validFrom);
                }
                if (d.valid_until) {
                    var validUntil = String(d.valid_until).replace(' ', 'T').slice(0, 16);
                    $f.find('[name="valid_until"]').val(validUntil);
                }
                
                // Populate categories
                return populateCategoriesContainer($container, d.categories || []);
            }).catch(function() {
                if (window.Swal) Swal.fire('Error', 'Failed to load discount data.', 'error');
                throw err;
            });
        }

        // Create/Edit click handled by global modal binder; modal opens immediately and data loads on show

        // Add category button handlers (create & edit)
        $(document).off('click.addCategoryCreate').on('click.addCategoryCreate', '#addCategoryBtnCreate', function(e) {
            e.preventDefault();
            var $container = $('#categoriesCreateContainer');
            
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
        $(document).off('click.addCategoryEdit').on('click.addCategoryEdit', '#addCategoryBtnEdit', function(e) {
            e.preventDefault();
            var $container = $('#categoriesEditContainer');
            fetchCategories().then(function(categories) {
                var $row = $('<div class="category-row mb-2"></div>');
                var $flexDiv = $('<div class="d-flex gap-2"></div>');
                var $select = buildCategorySelect(categories, null);
                var $removeBtn = $('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                $flexDiv.append($select).append($removeBtn);
                $row.append($flexDiv).append('<div class="invalid-feedback"></div>');
                $('#categoriesEditContainer').append($row);
                updateRemoveButtons($('#categoriesEditContainer'));
            });
        });

        // Remove category button handler (uses delegation on container)
        $(document).off('click.removeCategory').on('click.removeCategory', '#categoriesCreateContainer .remove-category, #categoriesEditContainer .remove-category', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.category-row');
            var $container = $btn.closest('#categoriesCreateContainer, #categoriesEditContainer');
            
            // Ensure at least one row remains
            if ($container.find('.category-row').length > 1) {
                $row.remove();
                updateRemoveButtons($container);
            }
        });

        // Form input validation handler
        $(document).off('input.discountValidation change.discountValidation')
            .on('input.discountValidation change.discountValidation', '#discountCreateForm input, #discountCreateForm select, #discountEditForm input, #discountEditForm select', function(e) {
            
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

        // Form submission handlers (create & edit)
        $(document).off('submit.discountCreate').on('submit.discountCreate', '#discountCreateForm', function(e) {
            e.preventDefault();
            var form = this;
            var $form = $(form);
            var formData = new FormData(form);
            var url = form.action;
            var $saveBtn = $('#discountCreateSaveBtn');
            var $spinner = $('#discountCreateSpinner');
            
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
                    var modal = document.getElementById('discountCreateModal');
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    
                    // Reload DataTable
                    if (window.DataTableInstances && window.DataTableInstances['discounts-table']) {
                        window.DataTableInstances['discounts-table'].ajax.reload(null, false);
                    }
                    
                    // Show success message
                    if (window.Swal) { Swal.fire('Success', data.message || 'Discount code created!', 'success'); }
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
            .catch(function() {
                if (window.Swal) {
                    Swal.fire('Error', 'An error occurred while saving.', 'error');
                }
            })
            .finally(function() {
                $saveBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            });
        });

        $(document).off('submit.discountEdit').on('submit.discountEdit', '#discountEditForm', function(e) {
            e.preventDefault();
            var form = this;
            var $form = $(form);
            var formData = new FormData(form);
            var url = form.action;
            var $saveBtn = $('#discountEditSaveBtn');
            var $spinner = $('#discountEditSpinner');
            $saveBtn.prop('disabled', true);
            $spinner.removeClass('d-none');
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.success){
                    var modal = document.getElementById('discountEditModal');
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    if (window.DataTableInstances && window.DataTableInstances['discounts-table']){
                        window.DataTableInstances['discounts-table'].ajax.reload(null, false);
                    }
                    if (window.Swal) { Swal.fire('Success', data.message || 'Discount code updated!', 'success'); }
                } else if (data.errors) {
                    $.each(data.errors, function(field, messages){
                        var $input = $('#discountEditForm [name="'+field+'"]');
                        if ($input.length){ $input.addClass('is-invalid'); $input.siblings('.invalid-feedback').text(messages[0]); }
                    });
                } else {
                    if (window.Swal) { Swal.fire('Error', data.message || 'Please fix the errors.', 'error'); }
                }
            })
            .catch(function(){ if (window.Swal) Swal.fire('Error','An error occurred','error'); })
            .finally(function(){ $saveBtn.prop('disabled', false); $spinner.addClass('d-none'); });
        });

        // Use Bootstrap data attributes to open modals; populate via modal events
        // Create: prepare fresh form and categories on every open
        $('#discountCreateModal').off('show.bs.modal').on('show.bs.modal', function(){
            resetCreateForm();
        });
        // Defensive: ensure one empty category row appears even if show fired before handlers attached
        $('#discountCreateModal').off('shown.bs.modal').on('shown.bs.modal', function(){
            var $c = $('#categoriesCreateContainer');
            if (!$c.children().length) {
                populateCategoriesContainer($c, []);
            }
        });

        // Edit: stash id from the trigger button, then fetch and prefill when opening
        $(document).on('click', '[data-action="edit"][data-modal="#discountEditModal"]', function(){
            var id = $(this).data('id');
            $('#discountEditModal').data('discount-id', id);
        });
        $('#discountEditModal').off('show.bs.modal').on('show.bs.modal', function(e){
            var id = $(e.relatedTarget).data('id') || $(this).data('discount-id');
            if (!id) { return; }
            resetEditForm();
            loadDiscountData(id, $('#discountEditForm'), $('#categoriesEditContainer'));
        });
        $('#discountEditModal').off('shown.bs.modal').on('shown.bs.modal', function(){
            var $c = $('#categoriesEditContainer');
            // If content did not arrive yet (slow network), refetch using id stored on form action
            if (!$c.children().length) {
                var action = $('#discountEditForm')[0].action || '';
                var m = action.match(/discount-codes\/(\d+)$/);
                if (m && m[1]) {
                    loadDiscountData(m[1], $('#discountEditForm'), $c);
                }
            }
        });

        // Save buttons
        window.saveDiscountCreate = function(){ $('#discountCreateForm').trigger('submit'); };
        window.saveDiscountEdit = function(){ $('#discountEditForm').trigger('submit'); };

    })();
    </script>
    @endpush
</x-app-layout>