<x-dashboard.layout>
    <x-slot:title>User Dashboard</x-slot:title>
    <x-slot:subtitle>Welcome back</x-slot:subtitle>
    <x-slot:sidebar>
        <a href="{{ route('user.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Dashboard</a>
    </x-slot:sidebar>
</x-dashboard.layout>