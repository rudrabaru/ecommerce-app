<x-header />

<section class="shop spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>{{ $category->name }}</h2>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($products as $p)
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product__item">
                    <div class="product__item__pic set-bg" data-setbg="{{ $p->image ? asset('storage/'.$p->image) : asset('img/product/product-1.jpg') }}">
                        <ul class="product__hover">
                            <li><a href="{{ route('shop.details', $p->id) }}"><img src="{{ asset('img/icon/search.png') }}" alt=""></a></li>
                        </ul>
                    </div>
                    <div class="product__item__text">
                        <h6>{{ $p->title }}</h6>
                        <h5>${{ number_format((float)$p->price, 2) }}</h5>
                        <a href="{{ route('shop.details', $p->id) }}" class="add-cart">View Details</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <h4>No products found in this category</h4>
            </div>
            @endforelse
        </div>

        <div class="row mt-3">
            <div class="col-12">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
</section>

<x-footer />


