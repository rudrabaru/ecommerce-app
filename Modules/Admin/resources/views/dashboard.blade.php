<x-dashboard.layout>
    <x-slot:title>Admin Dashboard</x-slot:title>
    <x-slot:subtitle>Overview & quick actions</x-slot:subtitle>
    <x-slot:sidebar>
        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Users</a>
    </x-slot:sidebar>
</x-dashboard.layout>