<!-- Product Modal for Admin -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Create Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="productId" name="product_id">
                    <input type="hidden" name="_method" id="productMethod" value="POST">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="provider_id" class="form-label">Provider <span class="text-danger">*</span></label>
                                <select class="form-select" id="provider_id" name="provider_id" required>
                                    <option value="">Select Provider</option>
                                    @foreach(\App\Models\User::whereHas('roles', function($q) { $q->where('name', 'provider'); })->get() as $provider)
                                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach(\Modules\Products\Models\Category::orderBy('name')->get() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Upload an image for the product (optional)</div>
                            </div>
                            
                            <div id="currentImage" class="mt-2" style="display: none;">
                                <img id="imagePreview" src="" alt="Current Image" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProductBtn" onclick="saveProduct()" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="productSaveSpinner" role="status" aria-hidden="true"></span>
                    Save Product
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openProductModal(productId = null) {
        // Reset form
        $('#productForm')[0].reset();
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#currentImage').hide();
        $('#saveProductBtn').prop('disabled', true);
        
        if (productId) {
            // Edit mode
            $('#productModalLabel').text('Edit Product');
            $('#productMethod').val('PUT');
            $('#productId').val(productId);
            
            // Load product data
            fetch(`/admin/products/${productId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                $('#title').val(data.title);
                $('#description').val(data.description);
                $('#price').val(data.price);
                $('#stock').val(data.stock);
                $('#provider_id').val(data.provider_id);
                $('#category_id').val(data.category_id);
                
                if (data.image) {
                    $('#imagePreview').attr('src', `/storage/${data.image}`);
                    $('#currentImage').show();
                }
                
                // Trigger validation after prefilling
                setTimeout(() => {
                    validateProductForm();
                    $('#title, #description, #price, #stock, #provider_id, #category_id').trigger('input');
                }, 100);
            })
            .catch(error => {
                console.error('Error loading product:', error);
                Swal.fire('Error', 'Error loading product data', 'error');
            });
        } else {
            // Create mode
            $('#productModalLabel').text('Create Product');
            $('#productMethod').val('POST');
            $('#productId').val('');
        }
        
        // Show modal immediately
        $('#productModal').modal('show');
    }
    
    function saveProduct() {
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        const productId = $('#productId').val();
        
        let url = '/admin/products';
        if (productId) {
            url += `/${productId}`;
        }
        
        // Show loading state
        $('#productSaveSpinner').removeClass('d-none');
        const saveBtn = document.getElementById('saveProductBtn');
        saveBtn.disabled = true;
        
        // Clear previous errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#productModal').modal('hide');
                if (window.DataTableInstances['products-table']) {
                    window.DataTableInstances['products-table'].ajax.reload();
                }
                Swal.fire('Success', data.message || 'Product saved successfully!', 'success');
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(data.errors[field][0]);
                    });
                }
                Swal.fire('Error', data.message || 'Please fix the errors above.', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving product:', error);
            Swal.fire('Error', 'An error occurred while saving the product.', 'error');
        })
        .finally(() => {
            $('#productSaveSpinner').addClass('d-none');
            saveBtn.disabled = false;
        });
    }
    
    function deleteProduct(productId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/products/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.DataTableInstances['products-table']) {
                            window.DataTableInstances['products-table'].ajax.reload();
                        }
                        Swal.fire('Deleted!', data.message || 'Product deleted successfully!', 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Error deleting product.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting product:', error);
                    Swal.fire('Error', 'An error occurred while deleting the product.', 'error');
                });
            }
        });
    }
    
    // Form validation for Products
    function validateProductForm() {
        const title = $('#title').val().trim();
        const description = $('#description').val().trim();
        const price = $('#price').val();
        const stock = $('#stock').val();
        const providerId = $('#provider_id').val();
        const categoryId = $('#category_id').val();
        
        // Clear previous validation states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        let isValid = true;
        
        // Title validation
        if (title === '') {
            $('#title').addClass('is-invalid');
            $('#title').siblings('.invalid-feedback').text('Title is required');
            isValid = false;
        } else if (title.length < 3) {
            $('#title').addClass('is-invalid');
            $('#title').siblings('.invalid-feedback').text('Title must be at least 3 characters');
            isValid = false;
        }
        
        // Description validation
        if (description === '') {
            $('#description').addClass('is-invalid');
            $('#description').siblings('.invalid-feedback').text('Description is required');
            isValid = false;
        } else if (description.length < 10) {
            $('#description').addClass('is-invalid');
            $('#description').siblings('.invalid-feedback').text('Description must be at least 10 characters');
            isValid = false;
        }
        
        // Price validation
        if (price === '') {
            $('#price').addClass('is-invalid');
            $('#price').siblings('.invalid-feedback').text('Price is required');
            isValid = false;
        } else if (isNaN(price) || parseFloat(price) < 0) {
            $('#price').addClass('is-invalid');
            $('#price').siblings('.invalid-feedback').text('Price must be a valid positive number');
            isValid = false;
        }
        
        // Stock validation
        if (stock === '') {
            $('#stock').addClass('is-invalid');
            $('#stock').siblings('.invalid-feedback').text('Stock is required');
            isValid = false;
        } else if (isNaN(stock) || parseInt(stock) < 0) {
            $('#stock').addClass('is-invalid');
            $('#stock').siblings('.invalid-feedback').text('Stock must be a valid positive number');
            isValid = false;
        }
        
        // Provider validation
        if (providerId === '') {
            $('#provider_id').addClass('is-invalid');
            $('#provider_id').siblings('.invalid-feedback').text('Provider is required');
            isValid = false;
        }
        
        // Category validation
        if (categoryId === '') {
            $('#category_id').addClass('is-invalid');
            $('#category_id').siblings('.invalid-feedback').text('Category is required');
            isValid = false;
        }
        
        $('#saveProductBtn').prop('disabled', !isValid);
    }
    
    // Add event listeners for form validation
    $(document).ready(function() {
        $('#title, #description, #price, #stock, #provider_id, #category_id').on('input change', validateProductForm);
    });
    
    // Handle DataTable actions
    $(document).on('click', '.edit-product', function() {
        const productId = $(this).data('id');
        openProductModal(productId);
    });
    
    $(document).on('click', '.delete-product', function() {
        const productId = $(this).data('id');
        deleteProduct(productId);
    });
</script>

