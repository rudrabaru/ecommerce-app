<x-app-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column">
                    @isset($title)
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-1">{{ $title }}</h1>
                                @isset($subtitle)
                                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                                @endisset
                            </div>
                        </div>
                    @endisset
                    
                    <div class="row">
                        <div class="col-12">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


