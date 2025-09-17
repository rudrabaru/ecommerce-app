<x-dashboard.layout>
    <x-slot:title>Manage Users</x-slot:title>
    <x-slot:subtitle>Promote users to provider</x-slot:subtitle>
    <x-slot:sidebar>
        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-50">Users</a>
    </x-slot:sidebar>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-600">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 text-sm text-red-600">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="py-2">Name</th>
                    <th class="py-2">Email</th>
                    <th class="py-2">Roles</th>
                    <th class="py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-t">
                        <td class="py-2">{{ $user->name }}</td>
                        <td class="py-2">{{ $user->email }}</td>
                        <td class="py-2">{{ implode(', ', $user->getRoleNames()->toArray()) }}</td>
                        <td class="py-2">
                            @if(!$user->hasRole('admin'))
                                <form method="POST" action="{{ route('admin.users.promote', $user) }}">
                                    @csrf
                                    <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-md">Promote to Provider</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</x-dashboard.layout>


