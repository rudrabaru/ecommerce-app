# DataTables & CRUD Modals Implementation Comparison

## ✅ CONSISTENT PATTERN: Users, Providers, Products, Categories

### DataTables Initialization:
- ✅ Uses global `initializeDataTables()` from `app.blade.php`
- ✅ Table structure: `data-dt-*` attributes
- ✅ Columns built from `<th data-column="...">` attributes
- ✅ No custom initialization code needed
- ✅ Auto-initializes on page load

### CRUD Modals:
- ✅ **Single modal for both create/edit** (not separate modals)
- ✅ Modal embedded in page (not loaded via AJAX)
- ✅ Bootstrap `show.bs.modal` event handler
- ✅ Create button: `data-bs-toggle="modal" data-bs-target="#modalId" data-action="create"`
- ✅ Edit button: `onclick="openXModal(id)"` + `data-bs-toggle="modal" data-bs-target="#modalId"`
- ✅ `openXModal(id)` function handles both create (id=null) and edit (id provided)
- ✅ Form reset on modal open
- ✅ AJAX data loading for edit mode
- ✅ Uses `window.reloadDataTable('table-id')` after save
- ✅ `ajaxPageLoaded` event listener for re-initialization

**Example (Users):**
```javascript
// Single modal function
window.openUserModal = function(userId = null) {
    // Reset form
    // If userId: load data via AJAX and prefill
    // If null: create mode
};

// Modal event
userModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button && button.dataset.action === 'create') {
        openUserModal(null);
    }
});

// Edit button in DataTable
onclick="openUserModal($row->id)"
```

---

## ❌ INCONSISTENT PATTERN: Discount Codes, Orders, Payments

### Discount Codes Issues:
1. ❌ **Uses TWO separate modals** (`discountCreateModal` and `discountEditModal`)
2. ❌ **No single `openDiscountModal(id)` function**
3. ❌ **Categories use dynamic add/remove** instead of single dropdown
4. ❌ **Edit modal doesn't prefill** existing data properly

### Orders Issues:
1. ❌ **Edit button uses `data-local-modal="1"`** instead of Bootstrap modal attributes
2. ❌ **No single product dropdown** - uses "+ Add item" button (might be intentional for multi-product)
3. ❌ **Edit modal event handler** only checks for `data-action="create"`, doesn't handle edit clicks

### Payments Issues:
1. ❌ **Edit buttons don't have `data-bs-toggle` or `data-payment-id`** attributes
2. ❌ **Modal event listener expects `data-payment-id`** but buttons only have `data-id`
3. ❌ **Click handler manually shows modal** instead of using Bootstrap attributes

---

## 🔧 REQUIRED FIXES

### 1. Products Modal (Issue #1, #2)
- Fix modal event to handle both create and edit
- Ensure create button clears any previous edit state

### 2. Discount Codes (Issue #3, #4)
- Merge two modals into one
- Add single category dropdown (or keep dynamic but make it clearer)
- Ensure edit mode prefills all data including categories

### 3. Orders (Issue #5, #6)
- Fix edit button to use Bootstrap modal attributes
- Add single product dropdown option (or clarify that multi-product is intentional)
- Fix modal event to handle edit clicks

### 4. Payments (Issue #7)
- Update edit buttons to include `data-bs-toggle="modal" data-bs-target="#paymentModal"`
- Fix modal event to read `data-id` instead of `data-payment-id`
- Or update buttons to use `data-payment-id`

