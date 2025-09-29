<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PortalLoginController extends Controller
{
    public function showUserLogin()
    {
        return view('auth.login', ['portal' => 'user']);
    }

    public function showAdminLogin()
    {
        return view('auth.login', ['portal' => 'admin']);
    }

    public function userLogin(Request $request)
    {
        $this->validateLogin($request);
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->hasRole('admin') || $user->hasRole('provider')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Unauthorized: Only users can log in here.']);
        }

        return redirect()->intended(route('home'));
    }

    public function adminLogin(Request $request)
    {
        $this->validateLogin($request);
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->hasRole('user')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Unauthorized: Only admins/providers can log in here.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    private function validateLogin(Request $request): void
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower($request->input('email')).'|'.$request->ip();
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }
        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey($request))]),
        ]);
    }
}



