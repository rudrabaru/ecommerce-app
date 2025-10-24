<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Categories</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Create Category
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categories-table" class="table table-hover" width="100%"
                           data-dt-url="{{ route('admin.categories.data') }}"
                           data-dt-page-length="25"
                           data-dt-order='[[0, "desc"]]'>
                        <thead class="table-light">
                            <tr>
                                <th data-column="id" data-width="60px">ID</th>
                                <th data-column="name">Name</th>
                                <th data-column="parent">Parent</th>
                                <th data-column="image" data-orderable="false" data-searchable="false" data-width="70px">Image</th>
                                <th data-column="description" data-width="30%">Description</th>
                                <th data-column="products_count" data-orderable="false" data-searchable="false" data-width="100px">Products</th>
                                <th data-column="actions" data-orderable="false" data-searchable="false" data-width="150px">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Create Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="categoryId" name="category_id">
                        <input type="hidden" name="_method" id="categoryMethod" value="POST">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">No Parent (Root Category)</option>
                                @foreach(\Modules\Products\Models\Category::whereNull('parent_id')->orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Leave blank to create a root category</div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Image <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <div class="invalid-feedback"></div>
                            <img src="" id="imagePreview" class="img-thumbnail mt-2 d-none" style="max-height: 120px;" />
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn" onclick="saveCategory()" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="categorySaveSpinner" role="status" aria-hidden="true"></span>
                        Save Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DataTable is now initialized globally - no need for individual initialization
        
        function openCategoryModal(categoryId = null) {
            // Reset form
            $('#categoryForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            
            if (categoryId) {
                // Edit mode
                $('#categoryModalLabel').text('Edit Category');
                $('#categoryMethod').val('PUT');
                $('#categoryId').val(categoryId);
                
                // Load category data
                fetch(`/admin/categories/${categoryId}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    $('#name').val(data.name);
                    $('#parent_id').val(data.parent_id || '');
                    if (data.image) {
                        $('#imagePreview').attr('src', data.image.startsWith('http') ? data.image : ('/storage/' + data.image)).removeClass('d-none');
                    } else {
                        $('#imagePreview').addClass('d-none');
                    }
                    $('#description').val(data.description || '');
                    
                    // Trigger validation after prefilling
                    setTimeout(() => {
                        // Trigger input events to update validation state
                        $('#name, #parent_id').trigger('input');
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading category:', error);
                    alert('Error loading category data');
                });
            } else {
                // Create mode
                $('#categoryModalLabel').text('Create Category');
                $('#categoryMethod').val('POST');
                $('#categoryId').val('');
            }
        }
        
        function saveCategory() {
            const form = document.getElementById('categoryForm');
            const formData = new FormData(form);
            const categoryId = $('#categoryId').val();
            
            let url = '/admin/categories';
            if (categoryId) {
                url += `/${categoryId}`;
            }
            
            // Show loading state
            $('#categorySaveSpinner').removeClass('d-none');
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
                    $('#categoryModal').modal('hide');
                    if (window.DataTableInstances['categories-table']) {
                        window.DataTableInstances['categories-table'].ajax.reload();
                    }
                    Swal.fire('Success', data.message || 'Category saved successfully!', 'success');
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
                console.error('Error saving category:', error);
                Swal.fire('Error', 'An error occurred while saving the category.', 'error');
            })
            .finally(() => {
                $('#categorySaveSpinner').addClass('d-none');
                saveBtn.disabled = false;
            });
        }
        
        function deleteCategory(categoryId) {
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
                    fetch(`/admin/categories/${categoryId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (window.DataTableInstances['categories-table']) {
                                window.DataTableInstances['categories-table'].ajax.reload();
                            }
                            Swal.fire('Deleted!', data.message || 'Category deleted successfully!', 'success');
                        } else {
                            Swal.fire('Error', data.message || 'Error deleting category.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting category:', error);
                        Swal.fire('Error', 'An error occurred while deleting the category.', 'error');
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
        
        // Form validation for Categories
        function validateCategoryForm() {
            const name = $('#name').val().trim();
            const desc = $('#description').val().trim();
            const imageOk = $('#categoryId').val() ? true : ($('#image').get(0).files.length > 0);
            const isValid = name !== '' && desc !== '' && imageOk;
            $('#saveCategoryBtn').prop('disabled', !isValid);
        }
        
        // Add event listeners for form validation
        $(document).ready(function() {
            $('#name, #parent_id, #image, #description').on('input change', validateCategoryForm);
            $('#image').on('change', function(){
                const file = this.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    $('#imagePreview').attr('src', url).removeClass('d-none');
                }
                validateCategoryForm();
            });
        });
    </script>
</x-app-layout>


