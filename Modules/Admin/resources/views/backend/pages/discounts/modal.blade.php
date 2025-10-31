<div class="modal fade" id="discountCreateModal" tabindex="-1" aria-labelledby="discountCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discountCreateModalLabel">Create Discount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discountCreateForm" method="POST" action="{{ route('admin.discounts.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
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
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addCategoryBtnCreate">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </label>
                            <div id="categoriesCreateContainer">
                                <!-- Categories will be dynamically populated here -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="discountCreateSaveBtn" onclick="window.saveDiscountCreate()">
                    <span class="spinner-border spinner-border-sm d-none" id="discountCreateSpinner" role="status" aria-hidden="true"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Discount Modal -->
<div class="modal fade" id="discountEditModal" tabindex="-1" aria-labelledby="discountEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discountEditModalLabel">Edit Discount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discountEditForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="discount_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Value <span class="text-danger">*</span></label>
                            <input type="number" name="discount_value" step="0.01" min="0.01" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum Order Amount</label>
                            <input type="number" name="minimum_order_amount" step="0.01" min="0" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" min="1" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Active</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid From <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="valid_from" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid Until <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="valid_until" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Categories <span class="text-danger">*</span></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addCategoryBtnEdit">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </label>
                            <div id="categoriesEditContainer"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="discountEditSaveBtn" onclick="window.saveDiscountEdit()">
                    <span class="spinner-border spinner-border-sm d-none" id="discountEditSpinner" role="status" aria-hidden="true"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .category-row .remove-category {
        display: none;
    }
    .category-row:not(:only-child) .remove-category {
        display: inline-flex;
    }
</style>
@endpush