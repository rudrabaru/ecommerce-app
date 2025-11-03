@props(['items' => []])

<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>{{ end($items)['label'] ?? '' }}</h4>
                    <div class="breadcrumb__links">
                        @foreach($items as $index => $item)
                            @if(isset($item['route']) && $index < count($items) - 1)
                                <a href="{{ $item['route'] }}">{{ $item['label'] }}</a>
                            @else
                                <span>{{ $item['label'] }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

