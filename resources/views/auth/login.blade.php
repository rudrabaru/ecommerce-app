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
            
            @if($errors->has('email') && str_contains($errors->first('email'), 'verify'))
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Email Verification Required
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Please check your email and click the verification link before logging in.</p>
                                <p class="mt-1">
                                    <a href="{{ route('verification.resend') }}" class="font-medium underline text-yellow-800 hover:text-yellow-900">
                                        Resend verification email
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
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
        // Enhanced error display functions
        function showVerificationError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Verification Required',
                    html: `
                        <div class="text-left">
                            <p class="mb-3">${message}</p>
                            <p class="mb-3">Please check your email and click the verification link before logging in.</p>
                            <p>
                                <a href="{{ route('verification.resend') }}" class="text-blue-600 underline hover:text-blue-800">
                                    Resend verification email
                                </a>
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#f59e0b'
                });
            } else {
                alert(message + '\n\nPlease check your email and click the verification link before logging in.');
            }
        }
        
        function showLoginError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: message,
                    confirmButtonText: 'OK'
                });
            } else {
                alert(message);
            }
        }
        
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
                        // Enhanced error display for email verification
                        const errorMessage = Object.values(res.errors).join('\n');
                        if (errorMessage.includes('verify')) {
                            // Show enhanced verification error
                            showVerificationError(errorMessage);
                        } else {
                            // Show regular error
                            showLoginError(errorMessage);
                        }
                    } else {
                        showLoginError('Login failed. Please try again.');
                    }
                }).catch(function(){ showLoginError('Login failed. Please try again.'); });
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
    </script>
    @endif
</x-guest-layout>
