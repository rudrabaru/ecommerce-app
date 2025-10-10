<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Providers</h1>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProviderModal">
                    <i class="fas fa-plus"></i> Create Provider
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="providers-table" class="table table-hover" width="100%"
                        data-dt-url="{{ route('admin.providers.data') }}"
                        data-dt-page-length="25"
                        data-dt-order='[[0, "desc"]]'>
                    <thead class="table-light">
                        <tr>
                            <th data-column="id" data-width="60px">ID</th>
                            <th data-column="name">Name</th>
                            <th data-column="email">Email</th>
                            <th data-column="created_at">Created At</th>
                            <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
                        </tr>
                    </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin::partials.create-provider-modal')
    @include('admin::partials.edit-provider-modal')

    @push('scripts')
        @vite('Modules/Admin/resources/js/providers.js')
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#providers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: $(table).data('dt-url'),
                pageLength: $(table).data('dt-page-length'),
                order: JSON.parse($(table).attr('data-dt-order')),
                columns: [
                    { data: 'id', name: 'id', width: '60px' },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            // Handle Create Provider Modal
            const createProviderModal = document.getElementById('createProviderModal');
            createProviderModal.addEventListener('shown.bs.modal', function() {
                document.getElementById('create_provider_name').focus();
            });

            // Handle Edit Provider Modal
            const editProviderModal = document.getElementById('editProviderModal');
            editProviderModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const providerId = button.getAttribute('data-id');
                loadProviderData(providerId);
            });

            // Load Provider Data for Editing
            function loadProviderData(providerId) {
                fetch(`/admin/users/${providerId}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const form = document.getElementById('editProviderForm');
                    form.action = `/admin/users/${providerId}`;
                    document.getElementById('edit_provider_name').value = data.user.name;
                    document.getElementById('edit_provider_email').value = data.user.email;
                    document.getElementById('edit_providerId').value = providerId;
                    
                    // Enable save button after data is loaded
                    document.getElementById('editProviderSaveBtn').disabled = false;
                })
                .catch(error => {
                    console.error('Error loading provider data:', error);
                    Swal.fire('Error', 'Failed to load provider data', 'error');
                });
            }

            // Form Validation and Submission
            ['createProviderForm', 'editProviderForm'].forEach(formId => {
                const form = document.getElementById(formId);
                const isCreate = formId === 'createProviderForm';
                const saveBtn = document.getElementById(isCreate ? 'createProviderSaveBtn' : 'editProviderSaveBtn');
                const spinner = document.getElementById(isCreate ? 'createProviderSpinner' : 'editProviderSpinner');

                // Validate form inputs
                form.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', validateForm);
                });

                function validateForm() {
                    const name = form.querySelector('[name="name"]').value.trim();
                    const email = form.querySelector('[name="email"]').value.trim();
                    const password = isCreate ? form.querySelector('[name="password"]').value : true;

                    const isValid = name.length > 0 && 
                                  email.length > 0 && 
                                  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) &&
                                  (isCreate ? password.length >= 8 : true);

                    saveBtn.disabled = !isValid;
                }
            });

            // Handle form submissions
            function submitForm(formId) {
                const form = document.getElementById(formId);
                const formData = new FormData(form);
                const saveBtn = form.querySelector('button[type="submit"]');
                const spinner = saveBtn.querySelector('.spinner-border');
                
                saveBtn.disabled = true;
                spinner.classList.remove('d-none');

                fetch(form.action, {
                    method: form.querySelector('[name="_method"]')?.value || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                        modal.hide();
                        form.reset();
                        table.ajax.reload();
                        Swal.fire('Success', data.message, 'success');
                    } else {
                        throw new Error(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', error.message || 'Failed to save provider', 'error');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    spinner.classList.add('d-none');
                });
            }

            // Expose submitForm to global scope for onclick handlers
            window.submitForm = submitForm;
        });
    </script>
    @endpush
</x-app-layout>
