@props(['categories'])

<ul id="categoryList" class="list-unstyled m-0">
    <li class="mb-2">
        <a href="{{ route('shop') }}" class="js-category-link {{ !request('category') ? 'active' : '' }}" data-id="">All Categories</a>
    </li>
    @foreach(($categories ?? collect()) as $pIndex => $parent)
        <li class="parent-item mb-1" data-parent-index="{{ $pIndex }}">
            <div class="d-flex align-items-center justify-content-between">
                <a href="#" class="text-decoration-none text-dark fw-semibold js-toggle-cat" data-id="{{ $parent->id }}">{{ $parent->name }}</a>
            </div>
            @if($parent->children && $parent->children->count())
                <ul class="subcat-list ms-3 mt-1" id="subcat-{{ $parent->id }}" style="display:none;">
                    @foreach($parent->children as $cIndex => $child)
                        <li class="subcat-item" data-sub-index="{{ $cIndex }}">
                            <a href="{{ route('shop', array_merge(request()->query(), ['category' => $child->id])) }}" class="js-category-link {{ request('category') == $child->id ? 'active' : '' }}" data-id="{{ $child->id }}">{{ $child->name }}</a>
                        </li>
                    @endforeach
                    <li class="small text-muted mt-1">
                        <a href="#" class="js-sub-see-more" data-parent="{{ $parent->id }}">See more</a>
                        <a href="#" class="js-sub-see-less d-none" data-parent="{{ $parent->id }}">See less</a>
                    </li>
                </ul>
            @endif
        </li>
    @endforeach
    <li class="small text-muted mt-2" id="parentSeeMoreRow">
        <a href="#" class="js-parent-see-more">See more</a>
        <a href="#" class="js-parent-see-less d-none">See less</a>
    </li>
</ul>


