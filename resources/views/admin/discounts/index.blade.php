<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Discount Codes</h1>
            <div>
                <button type="button" class="btn btn-primary" onclick="openDiscountModal()"><i class="fas fa-plus"></i> Create Discount Code</button>
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
                            <th>ID</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Categories</th>
                            <th>Valid From</th>
                            <th>Valid Until</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.discounts.modal')

    @push('scripts')
    <script>
        (function(){
            function start(){
                var $t = $('#discounts-table');
                if (!$t.length || !$.fn || !$.fn.dataTable) { return setTimeout(start, 50); }
                if ($.fn.dataTable.isDataTable($t)) return;
                window.DataTableInstances = window.DataTableInstances || {};
                window.DataTableInstances['discounts-table'] = $t.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: $t.data('dt-url'),
                    pageLength: $t.data('dt-page-length'),
                    order: JSON.parse($t.attr('data-dt-order')),
                    columns: [
                        { data: 'id', name: 'id', width: '60px' },
                        { data: 'code', name: 'code' },
                        { data: 'discount_type', name: 'discount_type' },
                        { data: 'discount_value', name: 'discount_value' },
                        { data: 'is_active', name: 'is_active' },
                        { data: 'categories', name: 'categories', orderable: false, searchable: false },
                        { data: 'valid_from', name: 'valid_from' },
                        { data: 'valid_until', name: 'valid_until' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false }
                    ]
                });
            }
            if (window.jQuery) start(); else window.addEventListener('load', start);

            // Modal logic
            window.openDiscountModal = function(id){
                resetDiscountForm();
                if (id) {
                    $('#discountModalLabel').text('Edit Discount');
                    $('#discountMethod').val('PUT');
                    $('#discountId').val(id);
                    fetch('/admin/discount-codes/'+id+'/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r=>r.json())
                        .then(data => {
                            const selected = (data.discount.category_id) ? data.discount.category_id : ((data.discount.categories||[]).map(c=>c.id)[0]||null);
                            fillCategories(data.categories, selected);
                            fillForm(data.discount);
                            $('#discountModal').modal('show');
                        })
                        .catch(()=> window.Swal && Swal.fire('Error','Failed to load discount','error'));
                } else {
                    $('#discountModalLabel').text('Create Discount');
                    $('#discountMethod').val('POST');
                    $('#discountId').val('');
                    fetch('/admin/discount-codes/create', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r=>r.json())
                        .then(data => { fillCategories(data.categories, []); $('#discountModal').modal('show'); })
                        .catch(()=> window.Swal && Swal.fire('Error','Failed to load categories','error'));
                }
            }

            function fillCategories(list, selected){
                var sel = document.getElementById('category_ids');
                sel.innerHTML = '<option value="">Select Category</option>';
                (list||[]).forEach(c => {
                    var opt = document.createElement('option');
                    opt.value = c.id; opt.textContent = c.name;
                    if (Array.isArray(selected) ? selected.includes(c.id) : (selected === c.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
            }

            function validateForm(){
                const code = $('#code').val().trim();
                const type = $('#discount_type').val();
                const value = parseFloat($('#discount_value').val());
                const validFrom = $('#valid_from').val();
                const validUntil = $('#valid_until').val();
                const isActive = $('#is_active').val();
                const categories = $('#category_ids').val() || [];

                let ok = true;
                const codeRegex = /^[A-Z0-9_-]+$/;
                if (!code || !codeRegex.test(code)) ok = false;
                if (!type || (type!=='fixed' && type!=='percentage')) ok = false;
                if (isNaN(value) || value < 1) ok = false;
                if (!validFrom || !validUntil || (new Date(validUntil) <= new Date(validFrom))) ok = false;
                if (isActive !== '0' && isActive !== '1') ok = false;
                if (categories.length < 1) ok = false;

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
                document.getElementById('category_ids').innerHTML = '';
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


