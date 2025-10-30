<x-app-layout>
    <div class="container-fluid">
        <h1 class="mb-4">Create Discount Code</h1>
        <form method="POST" action="{{ route('admin.discounts.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="discount_type" class="form-select" required>
                        <option value="fixed" {{ old('discount_type')==='fixed'?'selected':'' }}>Fixed</option>
                        <option value="percentage" {{ old('discount_type')==='percentage'?'selected':'' }}>Percentage</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Value</label>
                    <input type="number" name="discount_value" step="0.01" min="0.01" class="form-control" required value="{{ old('discount_value') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum Order Amount</label>
                    <input type="number" name="minimum_order_amount" step="0.01" min="0" class="form-control" value="{{ old('minimum_order_amount') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" name="usage_limit" min="1" class="form-control" value="{{ old('usage_limit') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Active</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active','1')==='1'?'selected':'' }}>Active</option>
                        <option value="0" {{ old('is_active')==='0'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valid From</label>
                    <input type="datetime-local" name="valid_from" class="form-control" value="{{ old('valid_from') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valid Until</label>
                    <input type="datetime-local" name="valid_until" class="form-control" value="{{ old('valid_until') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Categories (applies to)</label>
                    <select name="category_ids[]" class="form-select" multiple>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.discounts.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>


