<x-guest-layout>
    <h2 class="text-xl font-semibold mb-4">{{ $portal === 'admin' ? 'Admin / Provider Login' : 'User Login' }}</h2>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php($portal = $portal ?? 'user')
    <form id="plainLoginForm" method="POST" action="{{ $portal === 'admin' ? route('admin.login.submit') : route('login.submit') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ $portal === 'admin' ? __('Admin/Provider Login') : __('Log in') }}
            </x-primary-button>
        </div>
        @if ($portal === 'user')
            <div class="mt-4 text-right">
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" href="{{ route('admin.login') }}">Admin Portal</a>
            </div>
        @endif
    </form>

    @if(($portal ?? 'user') === 'user')
    <script>
    (function(){
        function init(){
            var form = document.getElementById('plainLoginForm');
            if (!form) return;
            form.addEventListener('submit', function(e){
                var params = new URLSearchParams(window.location.search);
                var redirect = params.get('redirect');
                if (!redirect) return; // no intercept, allow normal submit
                e.preventDefault();
                var data = new FormData(form);
                data.append('redirect', redirect);
                fetch('{{ route('login.ajax') }}?redirect=' + encodeURIComponent(redirect), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: data
                }).then(function(r){ return r.json(); }).then(function(res){
                    if (res && res.success){
                        try { sessionStorage.setItem('postLoginCartToast', '1'); } catch(e) {}
                        if (res.cart_count !== undefined && window.updateCartCount){ try { updateCartCount(res.cart_count); } catch(e) {} }
                        // Navigate back to the original page
                        window.location.href = redirect;
                    } else if (res && res.errors) {
                        // naive inline error display for email/password
                        alert(Object.values(res.errors).join('\n'));
                    } else {
                        alert('Login failed. Please try again.');
                    }
                }).catch(function(){ alert('Login failed. Please try again.'); });
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
    </script>
    @endif
</x-guest-layout>
