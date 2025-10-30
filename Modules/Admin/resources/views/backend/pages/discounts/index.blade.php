<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" id="createDiscountBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#discountModal" data-action="create" data-modal="#discountModal" data-local-modal="1">
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

        <template id="categoryRowTemplate">
            <div class="category-row mb-2">
                <div class="d-flex gap-2">
                    <select name="category_ids[]" class="form-select category-select" required>
                        <option value="">Select Category</option>
                    </select>
                    <button type="button" class="btn btn-outline-danger remove-category">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="invalid-feedback"></div>
            </div>
        </template>

    @push('scripts')
    <script>
        (function(){
            // DataTable is now initialized globally - no need for individual initialization

            // Ensure edit buttons carry consistent attributes and skip global handler
            // Add FontAwesome if not already included
            if (!document.querySelector('link[href*="font-awesome"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
                document.head.appendChild(link);
            }

            $(document).on('click', '.js-discount-edit', function(){
                $(this)
                    .attr('data-action','edit')
                    .attr('data-modal','#discountModal')
                    .attr('data-id', $(this).data('discount-id'))
                    .attr('data-local-modal','1');
            });

            // Page-scoped open function
            window.openDiscountModal = function(id = null){
                resetDiscountForm();
                const form = document.getElementById('discountForm');
                if (id) {
                    document.getElementById('discountModalLabel').textContent = 'Edit Discount';
                    document.getElementById('discountMethod').value = 'PUT';
                    document.getElementById('discountId').value = id;
                    if (form) form.action = `/admin/discount-codes/${id}`;
                    fetch(`/admin/discount-codes/${id}/edit`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r=>r.json())
                        .then(data => {
                                        const selectedIds = (data.discount.categories || []).map(c => c.id);
                            fillCategories(data.categories, selectedIds);
                            fillForm(data.discount);
                        })
                        .catch(()=> window.Swal && Swal.fire('Error','Failed to load discount','error'));
                } else {
                    document.getElementById('discountModalLabel').textContent = 'Create Discount';
                    document.getElementById('discountMethod').value = 'POST';
                    document.getElementById('discountId').value = '';
                    if (form) form.action = `{{ route('admin.discounts.store') }}`;
                    fetch('/admin/discount-codes/create', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r=>r.json())
                        .then(data => { fillCategories(data.categories, []); })
                        .catch(()=> window.Swal && Swal.fire('Error','Failed to load categories','error'));
                }
            };

            // Open via Bootstrap show event so relatedTarget tells us create/edit
            const $dm = $('#discountModal');
            $dm.on('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                const action = btn && (btn.getAttribute('data-action') || 'create');
                const id = btn && (btn.getAttribute('data-id') || btn.getAttribute('data-discount-id'));
                if (action === 'edit' && id) { window.openDiscountModal(id); }
                else { window.openDiscountModal(null); }
            });
            document.addEventListener('ajaxPageLoaded', function(){
                $('#discountModal').off('show.bs.modal').on('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    const action = btn && (btn.getAttribute('data-action') || 'create');
                    const id = btn && (btn.getAttribute('data-id') || btn.getAttribute('data-discount-id'));
                    if (action === 'edit' && id) { window.openDiscountModal(id); }
                    else { window.openDiscountModal(null); }
                });
            });

            function fillCategories(list, selected){
                const container = document.getElementById('categoriesContainer');
                container.innerHTML = ''; // Clear existing rows
                
                const selectedCategories = Array.isArray(selected) ? selected : [selected].filter(Boolean);
                const template = document.querySelector('#categoryRowTemplate');
                
                if (selectedCategories.length === 0) {
                    // Add one empty row
                    const row = template.content.cloneNode(true);
                    const select = row.querySelector('.category-select');
                    fillCategorySelect(select, list);
                    container.appendChild(row);
                } else {
                    // Add a row for each selected category
                    selectedCategories.forEach(categoryId => {
                        const row = template.content.cloneNode(true);
                        const select = row.querySelector('.category-select');
                        fillCategorySelect(select, list);
                        select.value = categoryId;
                        container.appendChild(row);
                    });
                }
                
                updateRemoveButtons();
            }
            
            function fillCategorySelect(select, categories) {
                select.innerHTML = '<option value="">Select Category</option>';
                (categories || []).forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    select.appendChild(opt);
                });
            }
            
            function updateRemoveButtons() {
                const container = document.getElementById('categoriesContainer');
                const buttons = container.querySelectorAll('.remove-category');
                buttons.forEach(btn => {
                    btn.style.display = container.children.length > 1 ? '' : 'none';
                });
            }

            function validateForm(){
                const code = $('#code').val().trim();
                const type = $('#discount_type').val();
                const value = parseFloat($('#discount_value').val());
                const validFrom = $('#valid_from').val();
                const validUntil = $('#valid_until').val();
                const isActive = $('#is_active').val();
                    const categorySelects = document.querySelectorAll('.category-select');

                let ok = true;
                const codeRegex = /^[A-Z0-9_-]+$/;
                
                // Clear previous validation states
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                
                if (!code || !codeRegex.test(code)) {
                    $('#code').addClass('is-invalid');
                    $('#code').siblings('.invalid-feedback').text('Code must contain only uppercase letters, numbers, hyphens, and underscores');
                    ok = false;
                }
                
                if (!type || (type!=='fixed' && type!=='percentage')) {
                    $('#discount_type').addClass('is-invalid');
                    $('#discount_type').siblings('.invalid-feedback').text('Please select a valid discount type');
                    ok = false;
                }
                
                if (isNaN(value) || value < 0.01) {
                    $('#discount_value').addClass('is-invalid');
                    $('#discount_value').siblings('.invalid-feedback').text('Value must be greater than 0');
                    ok = false;
                }
                
                if (!validFrom || !validUntil) {
                    if (!validFrom) {
                        $('#valid_from').addClass('is-invalid');
                        $('#valid_from').siblings('.invalid-feedback').text('Valid from date is required');
                    }
                    if (!validUntil) {
                        $('#valid_until').addClass('is-invalid');
                        $('#valid_until').siblings('.invalid-feedback').text('Valid until date is required');
                    }
                    ok = false;
                } else if (new Date(validUntil) <= new Date(validFrom)) {
                    $('#valid_until').addClass('is-invalid');
                    $('#valid_until').siblings('.invalid-feedback').text('Valid until must be after valid from date');
                    ok = false;
                }
                
                if (isActive !== '0' && isActive !== '1') {
                    $('#is_active').addClass('is-invalid');
                    $('#is_active').siblings('.invalid-feedback').text('Please select a valid status');
                    ok = false;
                }
                
                    // Check if at least one category is selected
                    const selectedCategories = new Set();
                    let hasCategories = false;
                
                    categorySelects.forEach(select => {
                        select.classList.remove('is-invalid');
                        select.nextElementSibling.textContent = '';
                    
                        if (select.value) {
                            hasCategories = true;
                            if (selectedCategories.has(select.value)) {
                                select.classList.add('is-invalid');
                                select.nextElementSibling.textContent = 'Category already selected';
                                ok = false;
                            }
                            selectedCategories.add(select.value);
                        }
                    });
                
                    if (!hasCategories) {
                        const firstSelect = categorySelects[0];
                        if (firstSelect) {
                            firstSelect.classList.add('is-invalid');
                            firstSelect.nextElementSibling.textContent = 'Please select at least one category';
                        }
                    ok = false;
                }

                $('#discountSaveBtn').prop('disabled', !ok);
            }

            function fillForm(d){
                $('#code').val((d.code||'').toString().toUpperCase());
                $('#discount_type').val(d.discount_type||'fixed');
                $('#discount_value').val(d.discount_value||'');
                $('#minimum_order_amount').val(d.minimum_order_amount||'');
                $('#usage_limit').val(d.usage_limit||'');
                $('#is_active').val(d.is_active?1:0);
                if (d.valid_from) $('#valid_from').val((d.valid_from+'').replace(' ','T').slice(0,16));
                if (d.valid_until) $('#valid_until').val((d.valid_until+'').replace(' ','T').slice(0,16));
                validateForm();
            }

            function resetDiscountForm(){
                $('#discountForm')[0].reset();
                $('#discountMethod').val('POST');
                $('#discountId').val('');
                $('#discountSpinner').addClass('d-none');
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid');
                    const container = document.getElementById('categoriesContainer');
                    if (container) {
                        container.innerHTML = '';
                        const template = document.querySelector('#categoryRowTemplate');
                        if (template) {
                            const row = template.content.cloneNode(true);
                            container.appendChild(row);
                            updateRemoveButtons();
                        }
                    }
            }

            $(document).on('input change', '#discountForm input, #discountForm select', function(e){
                if (e.target && e.target.id === 'code') {
                    e.target.value = e.target.value.toUpperCase();
                }
                validateForm();
            });

            window.saveDiscount = function(){
                const form = document.getElementById('discountForm');
                const id = $('#discountId').val();
                const method = $('#discountMethod').val();
                const url = id ? `/admin/discount-codes/${id}` : '/admin/discount-codes';

                const fd = new FormData(form);
                fd.append('_method', method);

                $('#discountSpinner').removeClass('d-none');
                $('#discountSaveBtn').prop('disabled', true);
                fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                })
                .then(async r => {
                    let data = {}; try { data = await r.json(); } catch(e) {}
                    if (!r.ok || data.success === false) throw data;
                    return data;
                })
                .then(() => {
                    $('#discountModal').modal('hide');
                    if ($.fn.dataTable.isDataTable('#discounts-table')) $('#discounts-table').DataTable().ajax.reload();
                    window.Swal && Swal.fire('Success','Saved','success');
                })
                .catch(err => {
                    const errors = (err && err.errors) || {};
                    Object.keys(errors).forEach(k => {
                        const el = document.getElementById(k);
                        if (el) { el.classList.add('is-invalid'); el.nextElementSibling && (el.nextElementSibling.textContent = errors[k][0]); }
                    });
                    window.Swal && Swal.fire('Error', (err && err.message) || 'Validation failed','error');
                })
                .finally(() => { $('#discountSpinner').addClass('d-none'); validateForm(); });
            }
        })();
    </script>
    @endpush
</x-app-layout>



