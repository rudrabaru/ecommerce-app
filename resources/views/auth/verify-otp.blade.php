<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Enter the 6-digit code sent to your email.
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.otp.verify') }}">
        @csrf
        <div>
            <x-input-label for="code" value="Verification Code" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-4 flex items-center gap-4">
            <x-primary-button>Verify</x-primary-button>
            <a href="{{ route('verification.otp.send') }}" class="underline text-sm">Resend code</a>
        </div>
    </form>
</x-guest-layout>


