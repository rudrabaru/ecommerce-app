@foreach($categories as $category)
<div class="d-flex align-items-center mb-1">
    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="img-thumbnail mr-2" style="width: 30px; height: 30px; object-fit: cover;">
    <span>{{ $category->name }}</span>
</div>
@endforeach