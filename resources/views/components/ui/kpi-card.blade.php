@props(['label' => '', 'value' => '', 'delta' => null, 'icon' => null, 'color' => 'indigo'])

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ $value }}</div>
            @if($delta)
                <div class="mt-1 text-xs {{ str_starts_with($delta,'+') ? 'text-emerald-600' : 'text-rose-600' }}">{{ $delta }} vs last period</div>
            @endif
        </div>
        @if($icon)
            <div class="w-10 h-10 grid place-content-center rounded-lg bg-{{ $color }}-50 text-{{ $color }}-600">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>


