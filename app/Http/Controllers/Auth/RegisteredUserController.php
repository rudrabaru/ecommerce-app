<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Models\EmailOtp;
use App\Mail\VerifyOtpMail;
use Illuminate\Support\Facades\Mail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'user',
            'password' => Hash::make($request->password),
        ]);

        // Assign default role to every newly registered user
        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        event(new Registered($user));

        Auth::login($user);

        // Generate and send OTP for first-time email verification
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailOtp::updateOrCreate(
            ['user_id' => $user->id, 'email' => $user->email, 'used' => false],
            ['code' => $code, 'expires_at' => now()->addMinutes(10)]
        );
        Mail::to($user->email)->send(new VerifyOtpMail($code));

        return redirect()->route('verification.otp.notice')->with('status', 'Verification code sent.');
    }
}
