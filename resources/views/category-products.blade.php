<x-header />

<section class="shop spad">
    <div class="container">
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="{{ route('categories.show', $category->id) }}" id="categoryProductsSearchForm" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search products in this category..." autocomplete="off">
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>{{ $category->name }}</h2>
                </div>
            </div>
        </div>

        <div id="products-grid" class="row">
            @include('components.product-cards', ['products' => $products])
        </div>
        <div class="row mt-3">
            <div class="col-12" id="pagination-container">
                @include('components.pagination', ['paginator' => $products])
            </div>
        </div>
    </div>
</section>

<x-footer />

<script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
<script>
(function(){
    function initSetBg(){
        $('.set-bg').each(function(){
            var bg = $(this).data('setbg');
            if (bg) $(this).css('background-image','url('+bg+')');
        });
    }
    function bindAjaxPagination(){
        $('#pagination-container').off('click', '.js-ajax-page').on('click', '.js-ajax-page', function(e){
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const $grid = $('#products-grid');
                    $grid.css('opacity', 0);
                    setTimeout(function(){
                        $grid.html(data.html);
                        $('#pagination-container').html(data.pagination);
                        $grid.css('opacity', 1);
                        history.pushState({}, '', url);
                        initSetBg();
                    }, 120);
                });
        });
    }
    function bindLiveSearch(){
        const $input = $('#categoryProductsSearchForm input[name="q"]');
        let t = null;
        $input.off('input').on('input', function(){
            clearTimeout(t);
            const q = this.value;
            t = setTimeout(function(){
                const url = new URL('{{ route('categories.show', $category->id) }}', window.location.origin);
                if (q) url.searchParams.set('q', q);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        const $grid = $('#products-grid');
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 120);
                    });
            }, 300);
        });
    }
    $(document).ready(function(){
        initSetBg();
        bindAjaxPagination();
        bindLiveSearch();
    });
    window.addEventListener('ajaxPageLoaded', function(){
        initSetBg();
        bindAjaxPagination();
    });
})();
</script>


