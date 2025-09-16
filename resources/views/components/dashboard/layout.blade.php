<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="flex">
            <aside class="hidden md:block w-64 bg-white border-r border-gray-200 min-h-screen">
                <div class="p-6 border-b">
                    <div class="text-lg font-semibold">{{ $title ?? 'Dashboard' }}</div>
                    <div class="text-sm text-gray-500">{{ $subtitle ?? '' }}</div>
                </div>
                <nav class="p-4 space-y-1">
                    {{ $sidebar ?? '' }}
                </nav>
            </aside>
            <main class="flex-1 p-4 md:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>


