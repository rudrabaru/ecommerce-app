<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="flex">
            <aside class="hidden md:block w-64 bg-white border-r border-gray-200 min-h-screen fixed md:static left-0 top-0 md:top-auto">
            </aside>
            <main class="flex-1 md:ml-64 p-4 md:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>


