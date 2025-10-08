<x-header />

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__text">
                        <h4>Shop</h4>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}">Home</a>
                            <span>Shop</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    <!-- Shop Section Begin -->
    <section class="shop spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="shop__sidebar">
                        <div class="shop__sidebar__search">
                            <form action="{{ route('shop') }}" method="GET" id="liveShopSearchForm">
                                <input type="text" name="q" placeholder="Search..." value="{{ request('q') }}" autocomplete="off">
                            </form>
                        </div>
                        <div class="shop__sidebar__accordion">
                            <div class="accordion" id="accordionExample">
                                <!-- Categories Card -->
                                <div class="card">
                                    <div class="card-heading">
                                        <a>Categories</a>
                                    </div>
                                    <div class="card-body">
                                        <div class="shop__sidebar__categories">
                                            @include('components.category-tree', ['categories' => $categories])
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Slider Card -->
                                <div class="card">
                                    <div class="card-heading">
                                        <a>Price</a>
                                    </div>
                                    <div class="card-body">
                                        <div class="shop__sidebar__price">
                                            <!-- Price Range Display -->
                                            <div class="price-range-display mb-3">
                                                <span class="d-inline-block">$<span id="minPriceDisplay">{{ request('min_price', floor($priceMinBound ?? 0)) }}</span></span>
                                                <span class="mx-2">-</span>
                                                <span class="d-inline-block">$<span id="maxPriceDisplay">{{ request('max_price', ceil($priceMaxBound ?? 2000)) }}</span></span>
                                            </div>

                                            <!-- Dual Range Slider -->
                                            <div class="price-slider-wrapper mb-3">
                                                <div class="price-slider-track"></div>
                                                <input type="range" 
                                                    id="priceMinRange" 
                                                    class="price-range-input" 
                                                    min="0" 
                                                    max="100" 
                                                    value="{{ request('min_price') ? round((request('min_price') - ($priceMinBound ?? 0)) / (($priceMaxBound ?? 2000) - ($priceMinBound ?? 0)) * 100) : 0 }}"
                                                    step="1">
                                                <input type="range" 
                                                    id="priceMaxRange" 
                                                    class="price-range-input" 
                                                    min="0" 
                                                    max="100" 
                                                    value="{{ request('max_price') ? round((request('max_price') - ($priceMinBound ?? 0)) / (($priceMaxBound ?? 2000) - ($priceMinBound ?? 0)) * 100) : 100 }}"
                                                    step="1">
                                            </div>

                                            <!-- Apply Button -->
                                            <button type="button" id="priceGo" class="btn btn-sm btn-dark btn-block mb-2">Go</button>

                                            <!-- Reset Link -->
                                            <div class="text-center mb-3">
                                                <a href="#" id="resetPrice" class="text-muted small">Reset price range</a>
                                            </div>

                                            <!-- Quick Price Ranges -->
                                            <div class="quick-price-links">
                                                <p class="small text-muted mb-2">< Quick Price Ranges ></p>
                                                <ul class="list-unstyled">
                                                    <li><a href="#" class="js-quick-price small" data-min="0" data-max="200">Up to $200</a></li>
                                                    <li><a href="#" class="js-quick-price small" data-min="200" data-max="500">$200 - $500</a></li>
                                                    <li><a href="#" class="js-quick-price small" data-min="500" data-max="750">$500 - $750</a></li>
                                                    <li><a href="#" class="js-quick-price small" data-min="750" data-max="{{ ceil($priceMaxBound ?? 20000) }}">Over $750</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="shop__product__option">
                        <div class="row align-items-center">
                            <div class="col-lg-6 col-md-6 col-sm-6"></div>
                            <div class="col-lg-6 col-md-6 col-sm-6 d-flex justify-content-end">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="shopSortSelect" class="form-select form-select-sm" style="max-width: 260px;" data-no-nice-select="1">
                                        <option value="featured" {{ request('sort') == 'featured' ? 'selected' : '' }}>Sort by: Featured</option>
                                        <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Sort by: Latest</option>
                                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Sort by: Price: Low to High</option>
                                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Sort by: Price: High to Low</option>
                                        <option value="best_reviews" {{ request('sort') == 'best_reviews' ? 'selected' : '' }}>Sort by: Best Customer Reviews</option>
                                        <option value="trending" {{ request('sort') == 'trending' ? 'selected' : '' }}>Sort by: Trending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="products-grid" class="row">
                        @include('components.product-cards', ['products' => $products, 'showNewBadge' => ($showNewBadge ?? false)])
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-12" id="pagination-container">
                            @include('components.pagination', ['paginator' => $products])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shop Section End -->

    <x-footer />

    <!-- Search Begin -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search here.....">
            </form>
        </div>
    </div>
    <!-- Search End -->

    <!-- Js Plugins -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('js/mixitup.min.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    
    <script>
    (function(){
        function initSetBg(){
            $('.set-bg').each(function(){
                var bg = $(this).data('setbg');
                if (bg) $(this).css('background-image','url('+bg+')');
            });
        }
        function initCategoryToggle(){
            // Toggle subcategory visibility (no caret icons, just nested list)
            $('#categoryList').on('click', '.js-toggle-cat', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const $sub = $('#subcat-'+id);
                
                // Use slideToggle with callback to ensure height calculation
                $sub.slideToggle(250, function() {
                    // Force recalculation of heights after animation
                    recalculateSidebarHeight();
                });
            });
            
            // Parent category see more/less in batches of 5
            const parentBatch = 5;
            const $parents = $('#categoryList > li.parent-item');
            const $parentMoreRow = $('#parentSeeMoreRow');
            function showParentRange(end){ 
                $parents.hide().slice(0, end).fadeIn(200, function() {
                    recalculateSidebarHeight();
                }); 
            }
            let parentShown = Math.min(parentBatch, $parents.length); 
            showParentRange(parentShown);
            
            function updateParentMore(){
                if (parentShown < $parents.length){
                    $parentMoreRow.find('.js-parent-see-more').removeClass('d-none');
                    $parentMoreRow.find('.js-parent-see-less').addClass('d-none');
                } else {
                    $parentMoreRow.find('.js-parent-see-more').addClass('d-none');
                    $parentMoreRow.find('.js-parent-see-less').removeClass('d-none');
                }
            }
            updateParentMore();
            
            $parentMoreRow.on('click', '.js-parent-see-more', function(e){ 
                e.preventDefault(); 
                parentShown = Math.min(parentShown + parentBatch, $parents.length); 
                showParentRange(parentShown); 
                updateParentMore(); 
            });
            
            $parentMoreRow.on('click', '.js-parent-see-less', function(e){ 
                e.preventDefault(); 
                parentShown = parentBatch; 
                showParentRange(parentShown); 
                updateParentMore(); 
            });

            // Subcategory see more/less in batches of 3 per parent (initialized on first expand)
            const subBatch = 3;
            $('#categoryList').on('click', '.js-toggle-cat', function(){
                const parentId = $(this).data('id');
                const $list = $('#subcat-'+parentId);
                if ($list.data('initialized')) return;
                const $items = $list.find('> li.subcat-item');
                let shown = Math.min(subBatch, $items.length); 
                $items.hide().slice(0, shown).fadeIn(200, function() {
                    recalculateSidebarHeight();
                });
                $list.data('initialized', true);
            });
            
            $('#categoryList').on('click', '.js-sub-see-more', function(e){ 
                e.preventDefault(); 
                const parentId = $(this).data('parent'); 
                const $list = $('#subcat-'+parentId); 
                const $items = $list.find('> li.subcat-item'); 
                let visible = $items.filter(':visible').length; 
                $items.slice(visible, visible+subBatch).fadeIn(200, function() {
                    recalculateSidebarHeight();
                }); 
                if ($items.filter(':visible').length >= $items.length){ 
                    $(this).addClass('d-none'); 
                    $list.find('.js-sub-see-less').removeClass('d-none'); 
                } 
            });
            
            $('#categoryList').on('click', '.js-sub-see-less', function(e){ 
                e.preventDefault(); 
                const parentId = $(this).data('parent'); 
                const $list = $('#subcat-'+parentId); 
                const $items = $list.find('> li.subcat-item'); 
                $items.hide().slice(0, subBatch).fadeIn(200, function() {
                    recalculateSidebarHeight();
                }); 
                $(this).addClass('d-none'); 
                $list.find('.js-sub-see-more').removeClass('d-none'); 
            });

            // Dynamic load via AJAX when clicking any category/subcategory
            $('#categoryList').on('click', '.js-category-link', function(e){
                e.preventDefault();
                const url = new URL($(this).attr('href'), window.location.origin);
                const params = new URLSearchParams(window.location.search);
                // preserve price and sort
                ['min_price','max_price','sort','q'].forEach(k => { if (params.get(k)) url.searchParams.set(k, params.get(k)); });
                const $grid = $('#products-grid');
                const loader = $('<div class="text-center w-100 py-3" id="gridLoader"><div class="spinner-border text-primary"></div></div>');
                $grid.css('opacity', 0.3).prepend(loader);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 100);
                    })
                    .finally(() => $('#gridLoader').remove());
            });
        }
        
        // Helper function to recalculate sidebar height
        function recalculateSidebarHeight() {
            // Force the browser to recalculate heights
            const $sidebar = $('.shop__sidebar');
            const $accordion = $('.shop__sidebar__accordion');
            const $categories = $('.shop__sidebar__categories');
            const $categoryList = $('#categoryList');
            
            // Remove any height constraints temporarily
            $sidebar.css('height', 'auto');
            $accordion.css('height', 'auto');
            $categories.css('height', 'auto');
            $categoryList.css('height', 'auto');
            
            // Let the browser calculate natural height
            setTimeout(function() {
                const naturalHeight = $categoryList[0].scrollHeight;
                $categoryList.css('min-height', naturalHeight + 'px');
            }, 50);
        }
        
        function bindAjaxPagination(){
            $('#pagination-container').off('click', '.js-ajax-page').on('click', '.js-ajax-page', function(e){
                e.preventDefault();
                const url = new URL($(this).attr('href'), window.location.origin);
                // preserve current sort selection on pagination
                const params = new URLSearchParams(window.location.search);
                const currentSort = params.get('sort');
                if (currentSort) url.searchParams.set('sort', currentSort);
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
        function bindSortDropdown(){
            const $select = $('#shopSortSelect');
            // Prevent theme from replacing with Nice Select so native dropdown opens
            if ($.fn.niceSelect && $select.length) {
                $select.each(function(){
                    const $el = $(this);
                    // If already converted by main.js, revert to native
                    if ($el.next('.nice-select').length) {
                        $el.next('.nice-select').remove();
                        $el.show();
                    }
                });
            }
            $select.off('change').on('change', function(){
                const chosen = this.value;
                const params = new URLSearchParams(window.location.search);
                const url = new URL("{{ route('shop') }}", window.location.origin);
                // preserve other filters
                ['q','category','min_price','max_price'].forEach(k => { if (params.get(k)) url.searchParams.set(k, params.get(k)); });
                url.searchParams.set('sort', chosen);

                const $grid = $('#products-grid');
                const loader = $('<div class="text-center w-100 py-3" id="gridLoader"><div class="spinner-border text-primary"></div></div>');
                $grid.css('opacity', 0.3).prepend(loader);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 120);
                    })
                    .finally(() => $('#gridLoader').remove());
            });
        }
        function bindLiveSearch(){
            const $input = $('#liveShopSearchForm input[name="q"]');
            let t = null;
            $input.off('input').on('input', function(){
                clearTimeout(t);
                const q = this.value;
                t = setTimeout(function(){
                    const url = new URL("{{ route('shop') }}", window.location.origin);
                    const params = new URLSearchParams(window.location.search);
                    // preserve filters
                    ['category','min_price','max_price','sort'].forEach(k => { if (params.get(k)) url.searchParams.set(k, params.get(k)); });
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
        
        function bindPriceSlider(){
            const globalMin = {{ (int)floor($priceMinBound ?? 0) }};
            const globalMax = {{ (int)ceil($priceMaxBound ?? 2000) }};
            const $minRange = $('#priceMinRange');
            const $maxRange = $('#priceMaxRange');
            const $minDisplay = $('#minPriceDisplay');
            const $maxDisplay = $('#maxPriceDisplay');
            
            // Update price display labels in real-time
            function updatePriceLabels() {
                let minVal = parseInt($minRange.val());
                let maxVal = parseInt($maxRange.val());
                
                // Prevent sliders from crossing
                if (minVal > maxVal - 5) {
                    minVal = maxVal - 5;
                    $minRange.val(minVal);
                }
                if (maxVal < minVal + 5) {
                    maxVal = minVal + 5;
                    $maxRange.val(maxVal);
                }
                
                const minPrice = Math.round(globalMin + (globalMax - globalMin) * (minVal / 100));
                const maxPrice = Math.round(globalMin + (globalMax - globalMin) * (maxVal / 100));
                
                $minDisplay.text(minPrice);
                $maxDisplay.text(maxPrice);
            }
            
            // Bind input events for real-time label update
            $minRange.on('input', updatePriceLabels);
            $maxRange.on('input', updatePriceLabels);
            
            // Apply price filter on Go button click
            $('#priceGo').on('click', function(){
                const params = new URLSearchParams(window.location.search);
                const minVal = parseInt($minRange.val(), 10);
                const maxVal = parseInt($maxRange.val(), 10);
                const minPrice = Math.round(globalMin + (globalMax - globalMin) * (minVal / 100));
                const maxPrice = Math.round(globalMin + (globalMax - globalMin) * (maxVal / 100));
                
                const url = new URL("{{ route('shop') }}", window.location.origin);
                ['q','category','sort'].forEach(k => { 
                    if (params.get(k)) url.searchParams.set(k, params.get(k)); 
                });
                url.searchParams.set('min_price', minPrice);
                url.searchParams.set('max_price', maxPrice);
                
                const $grid = $('#products-grid');
                const loader = $('<div class="text-center w-100 py-3" id="gridLoader"><div class="spinner-border text-primary"></div></div>');
                $grid.css('opacity', 0.3).prepend(loader);
                
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 120);
                    })
                    .finally(() => $('#gridLoader').remove());
            });
            
            // Reset price range
            $('#resetPrice').on('click', function(e){ 
                e.preventDefault(); 
                $minRange.val(0); 
                $maxRange.val(100); 
                updatePriceLabels();
                
                const params = new URLSearchParams(window.location.search);
                const url = new URL("{{ route('shop') }}", window.location.origin);
                ['q','category','sort'].forEach(k => { 
                    if (params.get(k)) url.searchParams.set(k, params.get(k)); 
                });
                // Remove price params
                url.searchParams.delete('min_price');
                url.searchParams.delete('max_price');
                
                const $grid = $('#products-grid');
                const loader = $('<div class="text-center w-100 py-3" id="gridLoader"><div class="spinner-border text-primary"></div></div>');
                $grid.css('opacity', 0.3).prepend(loader);
                
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 120);
                    })
                    .finally(() => $('#gridLoader').remove());
            });
            
            // Quick price range links
            $('.js-quick-price').on('click', function(e){
                e.preventDefault();
                const params = new URLSearchParams(window.location.search);
                const min = parseInt($(this).data('min'), 10);
                const max = parseInt($(this).data('max'), 10);
                
                const url = new URL("{{ route('shop') }}", window.location.origin);
                ['q','category','sort'].forEach(k => { 
                    if (params.get(k)) url.searchParams.set(k, params.get(k)); 
                });
                url.searchParams.set('min_price', min);
                url.searchParams.set('max_price', max);
                
                // Update slider positions
                const minPercent = Math.round((min - globalMin) / (globalMax - globalMin) * 100);
                const maxPercent = Math.round((max - globalMin) / (globalMax - globalMin) * 100);
                $minRange.val(minPercent);
                $maxRange.val(maxPercent);
                updatePriceLabels();
                
                const $grid = $('#products-grid');
                const loader = $('<div class="text-center w-100 py-3" id="gridLoader"><div class="spinner-border text-primary"></div></div>');
                $grid.css('opacity', 0.3).prepend(loader);
                
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        $grid.css('opacity', 0);
                        setTimeout(function(){
                            $grid.html(data.html);
                            $('#pagination-container').html(data.pagination);
                            $grid.css('opacity', 1);
                            history.pushState({}, '', url);
                            initSetBg();
                        }, 120);
                    })
                    .finally(() => $('#gridLoader').remove());
            });
        }
        
        $(document).ready(function(){
            initSetBg();
            bindAjaxPagination();
            bindLiveSearch();
            initCategoryToggle();
            bindPriceSlider();
            bindSortDropdown();
            
            // Initial height calculation
            recalculateSidebarHeight();
        });
        window.addEventListener('ajaxPageLoaded', function(){
            initSetBg();
            bindAjaxPagination();
            bindSortDropdown();
        });
    })();
    </script>
    
    <style>
        /* Enhanced sidebar styling to ensure proper expansion */
        .shop__sidebar {
            overflow: visible !important;
            height: auto !important;
            min-height: 300px;
        }
        
        .shop__sidebar__accordion {
            overflow: visible !important;
            height: auto !important;
        }
        
        .shop__sidebar__categories {
            overflow: visible !important;
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        #categoryList {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
            min-height: auto;
            transition: min-height 0.3s ease;
        }
        
        #categoryList li {
            list-style: none;
        }
        
        .subcat-list {
            overflow: visible !important;
            height: auto !important;
            padding-left: 15px;
        }
        
        /* Ensure parent containers don't clip content */
        .card-body {
            overflow: visible !important;
        }
        
        .accordion {
            overflow: visible !important;
        }
        
        .card {
            overflow: visible !important;
        }
        
        /* Smooth transitions for expanding/collapsing */
        .parent-item,
        .subcat-item {
            transition: opacity 0.2s ease;
        }
        
        /* Add spacing for better readability */
        .parent-item {
            margin-bottom: 8px;
        }
        
        .subcat-item {
            margin-bottom: 4px;
        }
        
        /* Active state styling */
        .js-category-link.active {
            color: #ca1515;
            font-weight: 600;
        }
        
        /* See more/less links styling */
        .js-parent-see-more,
        .js-parent-see-less,
        .js-sub-see-more,
        .js-sub-see-less {
            color: #666;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .js-parent-see-more:hover,
        .js-parent-see-less:hover,
        .js-sub-see-more:hover,
        .js-sub-see-less:hover {
            color: #ca1515;
        }

        /* Price Slider Styles */
        .price-range-display {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .price-slider-wrapper {
            position: relative;
            height: 6px;
            margin: 20px 0;
        }

        .price-slider-track {
            position: absolute;
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            top: 50%;
            transform: translateY(-50%);
        }

        .price-range-input {
            position: absolute;
            width: 100%;
            height: 6px;
            background: transparent;
            pointer-events: none;
            -webkit-appearance: none;
            top: 50%;
            transform: translateY(-50%);
        }

        .price-range-input::-webkit-slider-thumb {
            pointer-events: all;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #333;
            cursor: pointer;
            border: 3px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            position: relative;
            z-index: 3;
        }

        .price-range-input::-moz-range-thumb {
            pointer-events: all;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #333;
            cursor: pointer;
            border: 3px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            position: relative;
            z-index: 3;
        }

        .price-range-input::-webkit-slider-thumb:hover {
            background: #ca1515;
        }

        .price-range-input::-moz-range-thumb:hover {
            background: #ca1515;
        }

        .price-range-input:first-of-type {
            z-index: 2;
        }

        .price-range-input:last-of-type {
            z-index: 1;
        }

        #resetPrice {
            color: #666;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        #resetPrice:hover {
            color: #ca1515;
            text-decoration: underline;
        }

        .quick-price-links ul li {
            margin-bottom: 8px;
        }

        .quick-price-links ul li a {
            color: #333;
            text-decoration: none;
            transition: color 0.2s ease;
            display: block;
            padding: 4px 0;
        }

        .quick-price-links ul li a:hover {
            color: #ca1515;
        }

        .shop__sidebar__price {
            padding: 0;
        }
    </style>
    @include('components.cart-script')
</body>

</html>