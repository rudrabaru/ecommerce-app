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
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="{{ route('categories.index') }}" id="categoriesSearchForm" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search categories..." autocomplete="off">
                </form>
            </div>
        </div>

        <div id="categories-grid" class="row">
            @include('components.category-cards', ['categories' => $categories])
        </div>
        <div class="row mt-3">
            <div class="col-12" id="pagination-container">
                @include('components.pagination', ['paginator' => $categories])
            </div>
        </div>
    </div>
</section>

<x-footer />

<script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
<script>
(function(){
    function bindAjaxPagination(){
        $('#pagination-container').off('click', '.js-ajax-page').on('click', '.js-ajax-page', function(e){
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const $grid = $('#categories-grid');
                    $grid.css('opacity', 0);
                    setTimeout(function(){
                        $grid.html(data.html);
                        $('#pagination-container').html(data.pagination);
                        $grid.css('opacity', 1);
                        history.pushState({}, '', url);
                        $('.set-bg').each(function(){
                            var bg = $(this).data('setbg');
                            $(this).css('background-image', 'url(' + bg + ')');
                        });
                    }, 150);
                });
        });
    }
    function bindLiveSearch(){
        const $input = $('#categoriesSearchForm input[name="q"]');
        let t = null;
        $input.off('input').on('input', function(){
            clearTimeout(t);
            const q = this.value;
            t = setTimeout(function(){
                const url = new URL('{{ route('categories.index') }}', window.location.origin);
                if (q) url.searchParams.set('q', q);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        const $grid = $('#categories-grid');
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            $('.set-bg').each(function(){
                                var bg = $(this).data('setbg');
                                $(this).css('background-image', 'url(' + bg + ')');
                            });
                        }, 120);
                    });
            }, 300);
        });
    }
    $(document).ready(function(){ bindAjaxPagination(); bindLiveSearch(); });
    window.addEventListener('ajaxPageLoaded', bindAjaxPagination);
})();
</script>


