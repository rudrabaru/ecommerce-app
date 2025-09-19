{!! Hook::applyFilters(AdminFilterHook::USER_DROPDOWN_BEFORE, '') !!}

<div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
    <a class="flex items-center text-gray-700 dark:text-gray-300" href="#"
        @click.prevent="dropdownOpen = ! dropdownOpen">
        <span class="mr-3 h-8 w-8 overflow-hidden rounded-full">
            <img src="{{ auth()->user()->avatar_url ? auth()->user()->avatar_url : auth()->user()->getGravatarUrl() }}" alt="User" />
        </span>
    </a>

    <div x-show="dropdownOpen"
        class="absolute right-0 mt-[17px] flex w-[220px] flex-col rounded-md border bg-white dark:bg-gray-700 border-gray-200  p-3 shadow-theme-lg dark:border-gray-800 z-100"
        style="display: none">
        <div class="border-b border-gray-200 pb-2 dark:border-gray-800 mb-2">
            <span class="block font-medium text-gray-700 dark:text-gray-300">
                {{ auth()->user()->full_name }}
            </span>
            <span class="mt-0.5 block text-theme-sm text-gray-700 dark:text-gray-300">
                {{ auth()->user()->email }}
            </span>
        </div>

        {!! Hook::applyFilters(AdminFilterHook::USER_DROPDOWN_AFTER_USER_INFO, '') !!}

        <ul class="flex flex-col gap-1 border-b border-gray-200 pb-2 dark:border-gray-800">
            <li>
                <a href="{{ route('profile.edit') }}"
                    class="group flex items-center gap-3 rounded-md px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-gray-300">
                    <iconify-icon icon="lucide:user" width="20" height="20" class="fill-gray-500 group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"></iconify-icon>
                    {{ __('Edit profile') }}
                </a>
            </li>
        </ul>
        {!! Hook::applyFilters(AdminFilterHook::USER_DROPDOWN_AFTER_PROFILE_LINKS, '') !!}

        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit"
                class="group flex items-center gap-3 rounded-md px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-gray-300 mt-2 w-full">
                <iconify-icon icon="lucide:log-out" width="20" height="20" class="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300"></iconify-icon>
                {{ __('Logout') }}
            </button>
        </form>

        {!! Hook::applyFilters(AdminFilterHook::USER_DROPDOWN_AFTER_LOGOUT, '') !!}

        @if (session()->has('original_user_id'))
            @php
                $originalUser = \App\Models\User::find(session('original_user_id'));
            @endphp
            @if ($originalUser)
                <form method="POST" action="{{ route('admin.users.switch-back') }}" class="inline">
                    @csrf
                    <button type="submit"
                        class="group flex items-center gap-3 rounded-md px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-gray-300 mt-1 w-full">
                        <iconify-icon icon="lucide:arrow-left" width="16" height="16"></iconify-icon>
                        {{ __('Switch back to') }} {{ $originalUser->full_name }}
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>
{!! Hook::applyFilters(AdminFilterHook::USER_DROPDOWN_AFTER, '') !!}