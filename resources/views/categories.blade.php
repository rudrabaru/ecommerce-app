<x-header />

<section class="shop spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>Categories</h2>
                </div>
            </div>
        </div>
        <div class="row">
            @forelse($categories as $cat)
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="product__item">
                    <div class="product__item__pic set-bg" data-setbg="{{ $cat->image ? asset('storage/'.$cat->image) : asset('img/category-placeholder.png') }}">
                    </div>
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
        </div>
    </div>
</section>

<x-footer />


