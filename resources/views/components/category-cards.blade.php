@isset($categories)
    @forelse($categories as $cat)
    <div class="col-lg-4 col-md-4 col-sm-6 mb-4">
        <div class="product__item fade-in">
            <div class="product__item__pic set-bg" data-setbg="{{ $cat->image_url }}"></div>
            <div class="product__item__text">
                <h6>{{ $cat->name }}</h6>
                <p class="small text-muted">{{ \Illuminate\Support\Str::limit($cat->description, 80) }}</p>
                <a href="{{ route('categories.show', $cat->id) }}" class="add-cart">View Products</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <h4>No categories found</h4>
    </div>
    @endforelse
@endisset

