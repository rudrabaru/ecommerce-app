@extends('products::layouts.master')

@section('content')
    <h1 class="mb-3">Create Product</h1>
    @if(auth()->user()->hasRole('admin'))
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @else
        <form method="POST" action="{{ route('provider.products.store') }}" enctype="multipart/form-data">
    @endif
        @csrf
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" name="price" step="0.01" class="form-control" value="{{ old('price') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="{{ old('stock') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <option value="">-- Select --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control">
        </div>
        <button class="btn btn-primary">Save</button>
        @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
        @else
            <a href="{{ route('provider.products.index') }}" class="btn btn-secondary">Cancel</a>
        @endif
    </form>
@endsection


