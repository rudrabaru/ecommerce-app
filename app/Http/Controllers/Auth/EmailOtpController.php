<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyOtpMail;
use App\Models\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailOtpController extends Controller
{
    public function show(): View|RedirectResponse
    {
        // Guest-based OTP flow: require pending_user_id in session
        $pendingUserId = session('pending_user_id');
        if (!$pendingUserId) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp');
    }

    public function send(Request $request): RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_user_id');
        if (!$pendingUserId) {
            return back()->withErrors(['code' => 'Session expired. Please register again.']);
        }

        $user = \App\Models\User::find($pendingUserId);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $linkToken = bin2hex(random_bytes(20));
        EmailOtp::updateOrCreate(
            ['user_id' => $user->id, 'email' => $user->email, 'used' => false],
            ['code' => $code, 'link_token' => $linkToken, 'expires_at' => now()->addMinutes(15)]
        );
        $verifyUrl = url('/verify-email/link/'.$linkToken);
        Mail::to($user->email)->send(new VerifyOtpMail($code, $verifyUrl));
        return back()->with('status', 'Verification code sent.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $pendingUserId = $request->session()->get('pending_user_id');
        if (!$pendingUserId) {
            return back()->withErrors(['code' => 'Session expired. Please register again.']);
        }

        $user = \App\Models\User::find($pendingUserId);
        $otp = EmailOtp::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('used', false)
            ->orderByDesc('id')
            ->first();
        if (!$otp || $otp->code !== $request->code || $otp->expires_at->isPast()) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        // Mark verified for our custom status and, if desired, email
        $user->status = 'verified';
        $user->save();
        // Optionally also mark email as verified to reuse Laravel features
        if (method_exists($user, 'markEmailAsVerified')) {
            $user->markEmailAsVerified();
        }

        $otp->used = true;
        $otp->save();
        // Clear pending session and redirect to login (preserve optional redirect param for AJAX flows)
        $request->session()->forget('pending_user_id');
        $redirect = $request->query('redirect');
        if ($redirect) {
            return redirect()->route('login', ['redirect' => $redirect])->with('status', 'Email verified. Please login.');
        }

        return redirect()->route('login')->with('status', 'Email verified. Please login.');
    }
}
