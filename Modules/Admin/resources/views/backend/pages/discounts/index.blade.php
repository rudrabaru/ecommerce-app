<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" id="createDiscountBtn" class="btn btn-primary" data-action="create">
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
        // Prevent multiple initializations
        if (window.DiscountModalInitialized) {
            console.log('Discount modal handlers already initialized, skipping...');
            return;
        }
        window.DiscountModalInitialized = true;

        console.log('Initializing discount modal handlers...');

        // Helper: fetch categories
        async function fetchCategories() {
            try {
                const response = await $.ajax({
                    url: '/admin/discount-codes/create',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: false
                });
                return response.categories || [];
            } catch(e) {
                console.error('Failed to fetch categories:', e);
                if (window.Swal) Swal.fire('Error', 'Could not load categories.', 'error');
                return [];
            }
        }

        // Helper: build a category select with provided category options
        function buildCategorySelect(categories, value) {
            const $select = $('<select name="category_ids[]" class="form-select category-select" required><option value="">Select Category</option></select>');
            categories.forEach(cat => $select.append(`<option value="${cat.id}">${cat.name}</option>`));
            if (value) $select.val(value);
            return $select;
        }

        // Helper: update remove buttons
        function updateRemoveBtns($container) {
            const rowCount = $container.find('.category-row').length;
            $container.find('.remove-category').each(function(){
                $(this).toggle(rowCount > 1);
            });
        }

        // Helper: populate categories in existing container
        async function populateCategoriesContainer($container, selectedCategories = []) {
            const cats = await fetchCategories();
            $container.empty();
            
            if (selectedCategories.length > 0) {
                // Edit mode - create row for each selected category
                selectedCategories.forEach(category => {
                    const $row = $('<div class="category-row mb-2"><div class="d-flex gap-2"></div><div class="invalid-feedback"></div></div>');
                    const $select = buildCategorySelect(cats, category.id);
                    $row.find('.d-flex').append($select).append('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                    $container.append($row);
                });
            } else {
                // Create mode - single empty row
                const $row = $('<div class="category-row mb-2"><div class="d-flex gap-2"></div><div class="invalid-feedback"></div></div>');
                const $select = buildCategorySelect(cats);
                $row.find('.d-flex').append($select).append('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
                $container.append($row);
            }
            
            updateRemoveBtns($container);
        }

        // Create Discount button handler - Use body delegation for maximum compatibility
        $('body').off('click.discountCreate', '#createDiscountBtn').on('click.discountCreate', '#createDiscountBtn', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Create discount button clicked');
            
            const $form = $('#discountForm');
            const $container = $('#categoriesContainer');
            const modal = document.getElementById('discountModal');
            
            if (!modal) {
                console.error('Discount modal not found');
                return;
            }
            
            // Reset form
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $('#discountMethod').val('POST');
            $('#discountId').val('');
            $form[0].action = '/admin/discount-codes';
            $('#discountModalLabel').text('Create Discount');
            
            // Populate categories
            await populateCategoriesContainer($container, []);
            
            // Show modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        });

        // Edit Discount handler - Use body delegation with multiple selectors
        $('body').off('click.discountEdit', '.js-discount-edit').on('click.discountEdit', '.js-discount-edit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const editId = $btn.data('discount-id') || $btn.data('id');
            console.log('Edit discount button clicked, ID:', editId);
            
            const $form = $('#discountForm');
            const $container = $('#categoriesContainer');
            const modal = document.getElementById('discountModal');
            
            if (!modal) {
                console.error('Discount modal not found');
                return;
            }
            
            if (!editId) {
                console.error('No discount ID found');
                return;
            }
            
            // Reset form
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $('#discountMethod').val('PUT');
            $('#discountId').val(editId);
            $form[0].action = `/admin/discount-codes/${editId}`;
            $('#discountModalLabel').text('Edit Discount');
            
            try {
                // Fetch discount data
                const data = await $.ajax({ 
                    url: `/admin/discount-codes/${editId}/edit`, 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: false
                });
                
                const d = data.discount;
                
                // Prefill fields
                $('#code').val(d.code || '').trigger('input');
                $('#discount_type').val(d.discount_type || 'fixed');
                $('#discount_value').val(d.discount_value || '');
                $('#minimum_order_amount').val(d.minimum_order_amount || '');
                $('#usage_limit').val(d.usage_limit || '');
                $('#is_active').val(d.is_active ? 1 : 0);
                
                // Format datetime fields
                if (d.valid_from) {
                    $('#valid_from').val((d.valid_from+'').replace(' ','T').slice(0,16));
                }
                if (d.valid_until) {
                    $('#valid_until').val((d.valid_until+'').replace(' ','T').slice(0,16));
                }
                
                // Populate categories with selected values
                await populateCategoriesContainer($container, d.categories || []);
                
                // Show modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
            } catch(e) {
                console.error('Failed to load discount data:', e);
                if (window.Swal) Swal.fire('Error','Failed to load discount data','error');
            }
        });

        // Add category row handler
        $('body').off('click.addCategory', '#addCategoryBtn').on('click.addCategory', '#addCategoryBtn', async function(e){
            e.preventDefault();
            console.log('Add category button clicked');
            
            const $container = $('#categoriesContainer');
            const cats = await fetchCategories();
            
            const $row = $('<div class="category-row mb-2"><div class="d-flex gap-2"></div><div class="invalid-feedback"></div></div>');
            const $select = buildCategorySelect(cats);
            $row.find('.d-flex').append($select).append('<button type="button" class="btn btn-outline-danger remove-category"><i class="fas fa-times"></i></button>');
            $container.append($row);
            
            updateRemoveBtns($container);
        });

        // Remove category row handler
        $('body').off('click.removeCategory', '#categoriesContainer .remove-category').on('click.removeCategory', '#categoriesContainer .remove-category', function(e){
            e.preventDefault();
            console.log('Remove category button clicked');
            
            const $btn = $(this);
            const $row = $btn.closest('.category-row');
            const $container = $('#categoriesContainer');
            
            // Ensure at least one row remains
            if ($container.find('.category-row').length > 1) {
                $row.remove();
                updateRemoveBtns($container);
            }
        });

        // Validation and uppercasing code
        $('body').off('input.discountValidation change.discountValidation', '#discountForm input, #discountForm select')
            .on('input.discountValidation change.discountValidation', '#discountForm input, #discountForm select', function(e){
            if (e.target && e.target.id === 'code') {
                e.target.value = e.target.value.toUpperCase();
            }
            
            // Remove validation errors on input
            if ($(this).hasClass('is-invalid')) {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            }
        });

        // Form submission handler
        $('body').off('submit.discountForm', '#discountForm').on('submit.discountForm', '#discountForm', function(e) {
            e.preventDefault();
            console.log('Discount form submitted');
            
            const form = this;
            const formData = new FormData(form);
            const method = $('#discountMethod').val() || 'POST';
            const url = form.action;
            const $saveBtn = $('#discountSaveBtn');
            const $spinner = $('#discountSpinner');
            
            // Show loading state
            $saveBtn.prop('disabled', true);
            $spinner.removeClass('d-none');
            
            // Clear previous errors
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').text('');
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(r => r.json())
            .then(function(data){
                if (data.success) {
                    // Close modal
                    const modal = document.getElementById('discountModal');
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    
                    // Reload DataTable
                    if (window.DataTableInstances && window.DataTableInstances['discounts-table']) {
                        window.DataTableInstances['discounts-table'].ajax.reload();
                    }
                    
                    // Show success message
                    if (window.Swal) Swal.fire('Success', data.message || 'Discount code saved successfully!', 'success');
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(function(field){
                            const $input = $(`[name="${field}"]`);
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(data.errors[field][0]);
                            }
                        });
                    }
                    if (window.Swal) Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
                }
            })
            .catch(function(err){
                console.error('Form submission error:', err);
                if (window.Swal) Swal.fire('Error', 'An error occurred while saving.', 'error');
            })
            .finally(function(){
                $saveBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            });
        });

        // Save button handler
        window.saveDiscount = function() {
            console.log('Save discount called');
            $('#discountForm').trigger('submit');
        };

        console.log('Discount modal handlers initialized successfully');
    })();
    </script>
    @endpush
</x-app-layout>