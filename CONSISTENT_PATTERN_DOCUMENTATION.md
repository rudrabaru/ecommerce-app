# Consistent DataTables & CRUD Modals Pattern - All Admin/Provider Sections

## ✅ **STANDARD PATTERN (All Sections Now Follow This)**

### **1. DataTables Initialization:**
- ✅ **Uses global `initializeDataTables()`** from `resources/views/layouts/app.blade.php`
- ✅ **Table structure:** Uses `data-dt-*` attributes:
  - `data-dt-url` - AJAX endpoint URL
  - `data-dt-page-length` - Rows per page (default: 25)
  - `data-dt-order` - Default sort order (JSON format)
- ✅ **Column definitions:** Built automatically from `<th data-column="...">` attributes
- ✅ **No custom initialization code** in individual views
- ✅ **Auto-initializes** on page load via global script in `app.blade.php`
- ✅ **Re-initializes** on `ajaxPageLoaded` event (AJAX navigation)

**Code Pattern:**
```html
<table id="table-name" class="table table-hover" width="100%"
    data-dt-url="{{ route('admin.section.data') }}"
    data-dt-page-length="25"
    data-dt-order='[[0, "desc"]]'>
    <thead class="table-light">
        <tr>
            <th data-column="id" data-width="60px">ID</th>
            <th data-column="name">Name</th>
            <th data-column="actions" data-orderable="false" data-searchable="false">Actions</th>
        </tr>
    </thead>
</table>
```

### **2. CRUD Modals - Single Modal Pattern:**

#### **A. Modal Structure:**
- ✅ **Single modal for both create/edit** (not separate modals)
- ✅ **Modal embedded in page** (not loaded via AJAX)
- ✅ **Form with hidden fields:**
  - `<input type="hidden" id="itemId" name="item_id">`
  - `<input type="hidden" name="_method" id="itemMethod" value="POST">`
- ✅ **Form action URL** set dynamically via JavaScript

#### **B. Create Button:**
```html
<button type="button" class="btn btn-primary" 
    data-bs-toggle="modal" 
    data-bs-target="#itemModal" 
    data-action="create">
    <i class="fas fa-plus"></i> Create Item
</button>
```

#### **C. Edit Button (in DataTable):**
```php
// In Controller's data() method:
$btns .= '<button class="btn btn-sm btn-outline-primary" 
    data-id="'.$row->id.'" 
    data-bs-toggle="modal" 
    data-bs-target="#itemModal" 
    onclick="openItemModal('.$row->id.')" 
    title="Edit">';
$btns .= '<i class="fas fa-pencil-alt"></i></button>';
```

#### **D. JavaScript Pattern:**

```javascript
// 1. Ensure function is available immediately (before IIFE execution)
window.openItemModal = window.openItemModal || function() {};

// 2. Define the function (inside IIFE if needed)
window.openItemModal = function(itemId = null) {
    const form = document.getElementById('itemForm');
    const modalTitle = document.getElementById('itemModalLabel');
    
    // Reset form
    form.reset();
    document.querySelectorAll('.form-control').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    if (itemId) {
        // EDIT MODE
        modalTitle.textContent = 'Edit Item';
        document.getElementById('itemMethod').value = 'PUT';
        document.getElementById('itemId').value = itemId;
        form.action = `/admin/items/${itemId}`;
        
        // Load and prefill data
        fetch(`/admin/items/${itemId}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            // Handle both {item: {...}} or direct object
            const item = data.item || data;
            
            // Prefill all fields
            document.getElementById('name').value = item.name || '';
            document.getElementById('email').value = item.email || '';
            // ... other fields
        })
        .catch(error => {
            console.error('Error loading item:', error);
            if (window.Swal) Swal.fire('Error', 'Failed to load item data', 'error');
        });
    } else {
        // CREATE MODE
        modalTitle.textContent = 'Create Item';
        document.getElementById('itemMethod').value = 'POST';
        document.getElementById('itemId').value = '';
        form.action = '/admin/items';
    }
};

// 3. Initialize modal behavior
document.addEventListener('DOMContentLoaded', function() {
    const itemModal = document.getElementById('itemModal');
    if (itemModal) {
        itemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                if (button.dataset.action === 'create') {
                    openItemModal(null);
                } else {
                    const itemId = button.getAttribute('data-id');
                    if (itemId) {
                        // openItemModal is called via onclick, but ensure it's set
                    }
                }
            }
        });
    }
});

// 4. Re-initialize on AJAX page load
window.addEventListener('ajaxPageLoaded', function() {
    const itemModal = document.getElementById('itemModal');
    if (itemModal) {
        itemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                if (button.dataset.action === 'create') {
                    openItemModal(null);
                }
            }
        });
    }
});

// 5. Save function
window.saveItem = function() {
    const form = document.getElementById('itemForm');
    const formData = new FormData(form);
    const itemId = document.getElementById('itemId').value;
    const url = itemId ? `/admin/items/${itemId}` : '/admin/items';
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('itemModal'));
            if (modal) modal.hide();
            
            // Reload DataTable using global function
            window.reloadDataTable('items-table');
            
            if (window.Swal) {
                Swal.fire('Success', data.message || 'Item saved successfully', 'success');
            }
        } else {
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    const input = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = data.errors[key][0];
                        }
                    }
                });
            }
            
            if (window.Swal) {
                Swal.fire('Error', data.message || 'Validation error', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error saving item:', error);
        if (window.Swal) {
            Swal.fire('Error', 'An error occurred while saving', 'error');
        }
    });
};
```

### **3. Controller Response Pattern:**

#### **Edit Endpoint:**
```php
public function edit($id)
{
    $item = Item::findOrFail($id);
    $this->authorizeUpdate($item);

    if (request()->wantsJson() || request()->ajax()) {
        // Return direct object (not wrapped) for consistency
        return response()->json($item);
        // OR return wrapped if needed:
        // return response()->json(['item' => $item, 'related' => $related]);
    }

    return view('items.index'); // Redirect to index where modal handles edit
}
```

### **4. DataTable Reload Pattern:**
```javascript
// After successful save/delete
window.reloadDataTable('table-id');
```

---

## 📋 **IMPLEMENTATION CHECKLIST**

### ✅ All sections now follow this pattern:
- [x] **Users** - Single modal, proper prefilling, global functions
- [x] **Providers** - Single modal, proper prefilling, global functions
- [x] **Products** - Single modal, proper prefilling, fixed response handling
- [x] **Categories** - Single modal, proper prefilling
- [x] **Discount Codes** - Merged to single modal, category dropdown always visible, proper prefilling
- [x] **Orders** - Single modal, product dropdown always visible, proper prefilling
- [x] **Payments** - Single modal, proper prefilling

### 🔧 **Key Fixes Applied:**

1. **Function Availability:**
   - Added `window.openXModal = window.openXModal || function() {};` before IIFE to prevent "not defined" errors
   - Ensured functions are assigned to `window` object

2. **Response Handling:**
   - Products: Handle both `{product: {...}}` and direct object
   - Discount Codes: Handle `{discount: {...}}` structure
   - Payments: Handle direct object response

3. **Modal Event Handlers:**
   - Properly check for `data-action="create"` vs edit mode
   - Handle both Bootstrap modal events and onclick handlers

4. **Always Visible Dropdowns:**
   - Discount Codes: At least one category dropdown always visible
   - Orders: At least one product dropdown always visible

5. **AJAX Navigation:**
   - All modals re-initialize on `ajaxPageLoaded` event
   - DataTables re-initialize automatically via global function

---

## 🚫 **ANTI-PATTERNS (Avoid These):**

❌ **Don't use separate modals** for create/edit
❌ **Don't use custom DataTable initialization** - use global function
❌ **Don't access DataTable instances directly** - use `window.reloadDataTable()`
❌ **Don't define functions inside IIFE without exposing to window**
❌ **Don't use `data-local-modal`** - use Bootstrap modal attributes
❌ **Don't manually show modals** - let Bootstrap handle it via `data-bs-toggle`

---

## 📝 **Notes:**

- All functions are prefixed with `window.` to ensure global availability
- Functions are stubbed immediately to prevent "not defined" errors
- Modal event handlers check for both create and edit modes
- All AJAX responses handle both wrapped and direct object structures
- Form validation errors are shown inline with Bootstrap classes
- Success messages use SweetAlert2 when available

