<x-dashboard.layout>
    <x-slot:title>Provider Dashboard</x-slot:title>
    <x-slot:subtitle>Manage products & sales</x-slot:subtitle>
    <x-slot:sidebar>
        <a href="{{ route('provider.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Dashboard</a>
    </x-slot:sidebar>
</x-dashboard.layout>