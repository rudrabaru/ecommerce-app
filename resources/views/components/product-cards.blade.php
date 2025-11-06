@isset($products)
    @forelse($products as $p)
    <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="product__item">
            <div class="product__item__pic set-bg" data-setbg="{{ $p->image_url }}" style="background-image:url('{{ $p->image_url }}');">
                @if(!empty($showNewBadge))
                <span class="label">New</span>
                @endif
                <ul class="product__hover">
                    <li><a href="#"><img src="{{ asset('img/icon/heart.png') }}" alt=""> <span>Add to Wishlist</span></a></li>
                </ul>
            </div>
            <div class="product__item__text">
                <h6>{{ $p->title }}</h6>
                <a href="{{ route('shop.details', $p->id) }}" class="add-cart">View Details</a>
                <div class="rating">
                    @if($p->average_rating)
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa {{ $i <= round($p->average_rating) ? 'fa-star text-warning' : 'fa-star-o' }}"></i>
                        @endfor
                        <span class="ms-1">{{ number_format($p->average_rating, 1) }} ({{ $p->review_count }} {{ $p->review_count == 1 ? 'review' : 'reviews' }})</span>
                    @else
                        <span class="text-muted">No ratings yet</span>
                    @endif
                </div>
                <h5>${{ number_format((float)$p->price, 2) }}</h5>
                <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-dark mt-2 add-to-cart-btn">+ Add To Cart</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center py-5">
            <h4>No products found</h4>
        </div>
    </div>
    @endforelse
@endisset

