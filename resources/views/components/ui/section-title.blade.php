@props(['title' => '', 'description' => ''])

<div class="mb-4">
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    @if($description)
        <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
    @endif
</div>


