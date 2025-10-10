<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Products</h1>
            <div>
                @if(auth()->user()->hasRole('provider') || auth()->user()->hasRole('admin'))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="fas fa-plus"></i> Create Product
                    </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="products-table" class="table table-hover" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
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
                                @if(auth()->user()->hasRole('admin'))
                                <div class="mb-3">
                                    <label for="provider_id" class="form-label">Provider <span class="text-danger">*</span></label>
                                    <select class="form-select" id="provider_id" name="provider_id" required>
                                        <option value="">Select Provider</option>
                                        @foreach(\App\Models\User::whereHas('roles', fn($q) => $q->where('name','provider'))->orderBy('name')->get() as $provider)
                                            <option value="{{ $provider->id }}">{{ $provider->name }} ({{ $provider->email }})</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                @endif
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
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">
                        <span class="spinner-border spinner-border-sm d-none" id="saveSpinner" role="status" aria-hidden="true"></span>
                        Save Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize modal behavior
        document.addEventListener('DOMContentLoaded', function() {
            const productModal = document.getElementById('productModal');
            productModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const productId = button.getAttribute('data-product-id');
                openProductModal(productId);
            });
        });

        // DataTable is now initialized globally - no need for individual initialization
        
        window.openProductModal = function(productId = null) {
            // Reset form
            $('#productForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            $('#currentImage').hide();
            
            if (productId) {
                // Edit mode
                $('#productModalLabel').text('Edit Product');
                $('#productMethod').val('PUT');
                $('#productId').val(productId);
                
                // Load product data
                const prefix = '{{ auth()->user()->hasRole("admin") ? "admin" : "provider" }}';
                fetch(`/${prefix}/products/${productId}/edit`, {
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
                    $('#category_id').val(data.category_id);
                    
                    if (data.image) {
                        $('#imagePreview').attr('src', `/storage/${data.image}`);
                        $('#currentImage').show();
                    }
                    
                    // Trigger validation after prefilling
                    setTimeout(() => {
                        validateProductForm();
                        // Trigger input events to update validation state
                        $('#title, #description, #price, #stock, #category_id').trigger('input');
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading product:', error);
                    alert('Error loading product data');
                });
            } else {
                // Create mode
                $('#productModalLabel').text('Create Product');
                $('#productMethod').val('POST');
                $('#productId').val('');
            }
        }
        
        window.saveProduct = function() {
            const form = document.getElementById('productForm');
            const formData = new FormData(form);
            const productId = $('#productId').val();
            const prefix = '{{ auth()->user()->hasRole("admin") ? "admin" : "provider" }}';
            
            let url = `/${prefix}/products`;
            if (productId) {
                url += `/${productId}`;
            }
            
            // Show loading state
            $('#saveSpinner').removeClass('d-none');
            const saveBtn = event.target;
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
                    // Handle validation errors
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
                $('#saveSpinner').addClass('d-none');
                saveBtn.disabled = false;
            });
        }
        
        window.deleteProduct = function(productId) {
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
                    const prefix = '{{ auth()->user()->hasRole("admin") ? "admin" : "provider" }}';
                    
                    fetch(`/${prefix}/products/${productId}`, {
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
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Remove existing alerts
            $('.alert').remove();
            
            // Add new alert at the top of content
            $('.container-fluid').prepend(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        // Form validation for Products
        function validateProductForm() {
            const title = $('#title').val().trim();
            const description = $('#description').val().trim();
            const price = $('#price').val();
            const stock = $('#stock').val();
            const categoryId = $('#category_id').val();
            
            let isValid = title !== '' && description !== '' && price !== '' && stock !== '' && categoryId !== '';
            
            // Price validation
            if (price && (isNaN(price) || parseFloat(price) < 0)) {
                isValid = false;
            }
            
            // Stock validation
            if (stock && (isNaN(stock) || parseInt(stock) < 0)) {
                isValid = false;
            }
            
            $('#saveSpinner').parent().prop('disabled', !isValid);
        }
        
        // Add event listeners for form validation
        $(document).ready(function() {
            $('#title, #description, #price, #stock, #category_id').on('input change', validateProductForm);
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
</x-app-layout>
