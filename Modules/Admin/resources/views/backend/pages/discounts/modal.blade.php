<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discountModalLabel">Create Discount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discountForm" method="POST" action="{{ route('admin.discounts.store') }}">
                    @csrf
                    <input type="hidden" id="discountId" name="discount_id">
                    <input type="hidden" id="discountMethod" name="_method" value="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select id="discount_type" name="discount_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Value <span class="text-danger">*</span></label>
                            <input type="number" id="discount_value" name="discount_value" step="0.01" min="0.01" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum Order Amount</label>
                            <input type="number" id="minimum_order_amount" name="minimum_order_amount" step="0.01" min="0" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" id="usage_limit" name="usage_limit" min="1" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Active</label>
                            <select id="is_active" name="is_active" class="form-select">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid From <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="valid_from" name="valid_from" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid Until <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="valid_until" name="valid_until" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Categories <span class="text-danger">*</span></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addCategoryBtn">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </label>
                            <div id="categoriesContainer">
                                <div class="category-row mb-2">
                                    <div class="d-flex gap-2">
                                        <select name="category_ids[]" class="form-select category-select" required>
                                            <option value="">Select Category</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-danger remove-category" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="discountSaveBtn" onclick="window.saveDiscount()" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="discountSpinner" role="status" aria-hidden="true"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .category-row .remove-category {
        visibility: hidden;
    }
    .category-row:not(:only-child) .remove-category {
        visibility: visible;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let categoriesCache = null;

        async function loadCategories() {
            if (categoriesCache) return categoriesCache;
            
            try {
                const response = await fetch('/admin/discount-codes/create', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                categoriesCache = data.categories;
                return categoriesCache;
            } catch (error) {
                console.error('Failed to load categories:', error);
                window.Swal?.fire('Error', 'Failed to load categories', 'error');
                return [];
            }
        }

        function createCategoryRow() {
            const template = document.querySelector('.category-row');
            const newRow = template.cloneNode(true);
            newRow.querySelector('select').value = '';
            return newRow;
        }

        async function populateCategorySelect(select) {
            const categories = await loadCategories();
            select.innerHTML = '<option value="">Select Category</option>';
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
        }

        // Initialize the first category dropdown
        populateCategorySelect(document.querySelector('.category-select'));

        // Add Category button handler
        document.getElementById('addCategoryBtn').addEventListener('click', function() {
            const container = document.getElementById('categoriesContainer');
            const newRow = createCategoryRow();
            container.appendChild(newRow);
            populateCategorySelect(newRow.querySelector('.category-select'));

            // Show/hide remove buttons based on number of rows
            document.querySelectorAll('.remove-category').forEach(btn => {
                btn.style.display = container.children.length > 1 ? '' : 'none';
            });
        });

        // Remove category handler
        document.getElementById('categoriesContainer').addEventListener('click', function(e) {
            if (e.target.closest('.remove-category')) {
                const row = e.target.closest('.category-row');
                const container = row.parentElement;
                row.remove();

                // Show/hide remove buttons based on number of rows
                document.querySelectorAll('.remove-category').forEach(btn => {
                    btn.style.display = container.children.length > 1 ? '' : 'none';
                });
            }
        });

        // Update form validation to check for duplicate categories
        const originalValidateForm = window.validateForm;
        window.validateForm = function() {
            let ok = originalValidateForm();
            
            // Check for duplicate categories
            const selectedCategories = new Set();
            document.querySelectorAll('.category-select').forEach(select => {
                const value = select.value;
                if (value && selectedCategories.has(value)) {
                    select.classList.add('is-invalid');
                    select.nextElementSibling.textContent = 'Category already selected';
                    ok = false;
                }
                selectedCategories.add(value);
            });

            return ok;
        };

        // Reset form handler
        const originalResetForm = window.resetDiscountForm;
        window.resetDiscountForm = function() {
            originalResetForm();
            
            // Reset categories to single empty row
            const container = document.getElementById('categoriesContainer');
            const firstRow = container.querySelector('.category-row');
            container.innerHTML = '';
            container.appendChild(firstRow);
            firstRow.querySelector('select').value = '';
            firstRow.querySelector('.remove-category').style.display = 'none';
        };

        // Fill form handler
        const originalFillForm = window.fillForm;
        window.fillForm = function(d) {
            originalFillForm(d);
            
            // Handle multiple categories
            const container = document.getElementById('categoriesContainer');
            container.innerHTML = ''; // Clear existing rows
            
            const categories = d.categories || [];
            categories.forEach((category, index) => {
                const row = createCategoryRow();
                container.appendChild(row);
                const select = row.querySelector('.category-select');
                populateCategorySelect(select).then(() => {
                    select.value = category.id;
                });
            });

            if (categories.length === 0) {
                const row = createCategoryRow();
                container.appendChild(row);
                populateCategorySelect(row.querySelector('.category-select'));
            }

            // Show/hide remove buttons
            document.querySelectorAll('.remove-category').forEach(btn => {
                btn.style.display = container.children.length > 1 ? '' : 'none';
            });
        };
    });
</script>
@endpush

