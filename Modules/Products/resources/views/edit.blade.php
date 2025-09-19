@extends('products::layouts.master')

@section('content')
    <h1 class="mb-3">Edit Product</h1>
    @if(auth()->user()->hasRole('admin'))
        <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
    @else
        <form method="POST" action="{{ route('provider.products.update', $product->id) }}" enctype="multipart/form-data">
    @endif
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $product->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required>{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" name="price" step="0.01" class="form-control" value="{{ old('price', $product->price) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control">
            @if($product->image)
                <div class="mt-2"><img src="{{ asset('storage/'.$product->image) }}" alt="" width="120"></div>
            @endif
        </div>
        <button class="btn btn-primary">Update</button>
        @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
        @else
            <a href="{{ route('provider.products.index') }}" class="btn btn-secondary">Cancel</a>
        @endif
    </form>
@endsection


