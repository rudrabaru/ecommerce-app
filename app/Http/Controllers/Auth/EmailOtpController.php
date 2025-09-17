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
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        return view('auth.verify-otp');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailOtp::updateOrCreate(
            ['user_id' => $user->id, 'email' => $user->email, 'used' => false],
            ['code' => $code, 'expires_at' => now()->addMinutes(10)]
        );
        Mail::to($user->email)->send(new VerifyOtpMail($code));
        return back()->with('status', 'Verification code sent.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $user = Auth::user();
        $otp = EmailOtp::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('used', false)
            ->orderByDesc('id')
            ->first();
        if (!$otp || $otp->code !== $request->code || $otp->expires_at->isPast()) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }
        $user->markEmailAsVerified();
        $otp->used = true;
        $otp->save();
        return redirect()->route('dashboard');
    }
}


