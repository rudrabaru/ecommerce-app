@props(['paginator'])
@php
    $isPaginator = $paginator instanceof Illuminate\Contracts\Pagination\Paginator || $paginator instanceof Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if ($isPaginator && $paginator->hasPages())
<div class="d-flex justify-content-end">
    <nav aria-label="Pagination">
        <ul class="pagination mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link js-ajax-page" href="{{ $paginator->previousPageUrl() }}">&laquo;</a>
            </li>

            @php
                $current = $paginator->currentPage();
                $last = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : $current;
                $window = 2;
                $pages = [];
                for ($i = 1; $i <= $last; $i++) {
                    if ($i == 1 || $i == $last || ($i >= $current - $window && $i <= $current + $window)) {
                        $pages[] = $i;
                    }
                }
                $display = [];
                $prev = 0;
                foreach ($pages as $p) {
                    if ($prev && $p > $prev + 1) { $display[] = '…'; }
                    $display[] = $p; $prev = $p;
                }
            @endphp

            @foreach ($display as $entry)
                @if ($entry === '…')
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @else
                    @php($page = (int)$entry)
                    <li class="page-item {{ $page === $current ? 'active' : '' }}">
                        <a class="page-link js-ajax-page" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link js-ajax-page" href="{{ $paginator->nextPageUrl() }}">&raquo;</a>
            </li>
        </ul>
    </nav>
</div>
@endif

