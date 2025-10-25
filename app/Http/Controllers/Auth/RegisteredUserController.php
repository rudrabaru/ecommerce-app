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
use Spatie\Permission\Models\Role;

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
            'password' => Hash::make($request->password),
        ]);

        // Ensure default role exists and assign to user; also persist role_id
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        if ($role && $role->id) {
            $user->role_id = $role->id;
            $user->save();
        }

        // Avoid firing the default Registered event for storefront users to prevent
        // Laravel's automatic email verification notification from being sent.
        // We already send a single combined email (OTP + verify link) below.
        // For non-user roles (e.g., admin/provider), we keep Laravel's default flow.
        $role = $role ?? null; // preserve reference from below if exists
        $shouldFireDefaultRegistered = false;
        try {
            $userRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $isStorefrontUser = $user->hasRole('user') || ((int)($user->role_id) === (int)($userRole->id ?? 0));
            $shouldFireDefaultRegistered = ! $isStorefrontUser;
        } catch (\Throwable $throwable) {
            // If roles are not resolvable for some reason, default to firing the event to be safe
            $shouldFireDefaultRegistered = true;
        }

        if ($shouldFireDefaultRegistered) {
            event(new Registered($user));
        }

        // For role user (role_id = 3), set status and require OTP before login
        if ($user->role_id) {
            // Try to resolve 'user' role id dynamically
            $userRoleId = $role->id ?? \Spatie\Permission\Models\Role::where('name', 'user')->value('id');
            if ($userRoleId && (int)$user->role_id === (int)$userRoleId) {
                // Set unverified; do not log in yet
                $user->status = 'unverified';
                $user->save();

                // Check for existing unexpired OTP first
                $existingOtp = EmailOtp::where('user_id', $user->id)
                    ->where('email', $user->email)
                    ->where('used', false)
                    ->where('expires_at', '>', now())
                    ->first();

                if ($existingOtp) {
                    // Reuse existing OTP
                    $verifyUrl = url('/verify-email/link/'.$existingOtp->link_token);
                    try {
                        Mail::to($user->email)->send(new VerifyOtpMail($existingOtp->code, $verifyUrl));
                        \Log::info('Verification email sent (reused OTP)', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'otp_code' => $existingOtp->code
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification email (reused OTP)', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                } else {
                    // Generate new OTP
                    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $linkToken = bin2hex(random_bytes(20));
                    EmailOtp::updateOrCreate(
                        ['user_id' => $user->id, 'email' => $user->email, 'used' => false],
                        ['code' => $code, 'link_token' => $linkToken, 'expires_at' => now()->addMinutes(15)]
                    );
                    $verifyUrl = url('/verify-email/link/'.$linkToken);
                    try {
                        Mail::to($user->email)->send(new VerifyOtpMail($code, $verifyUrl));
                        \Log::info('Verification email sent (new OTP)', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'otp_code' => $code
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification email (new OTP)', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }

                // Store pending user id for guest-based OTP flow
                $request->session()->put('pending_user_id', $user->id);
                $redirect = $request->query('redirect');
                if ($redirect) {
                    return redirect()->route('verification.otp.notice', ['redirect' => $redirect])->with('status', 'Verification code sent.');
                }

                return redirect()->route('verification.otp.notice')->with('status', 'Verification code sent.');
            }
        }

        // Admins/Providers: proceed normally
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }
}
