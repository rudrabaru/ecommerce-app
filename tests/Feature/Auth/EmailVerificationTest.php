<?php

use App\Models\User;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyOtpMail;
use App\Events\UserEmailVerified;
use Illuminate\Support\Facades\Event;

test('otp reuse logic works correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    
    // Create an existing unexpired OTP
    $existingOtp = EmailOtp::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'code' => '123456',
        'link_token' => 'existing-token',
        'expires_at' => now()->addMinutes(15),
        'used' => false
    ]);

    Mail::fake();

    // Simulate sending verification email
    $response = $this->post('/resend-verification', [
        'email' => $user->email
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    // Should reuse existing OTP, not create new one
    $this->assertDatabaseCount('email_otps', 1);
    $this->assertDatabaseHas('email_otps', [
        'user_id' => $user->id,
        'code' => '123456',
        'link_token' => 'existing-token'
    ]);

    Mail::assertSent(VerifyOtpMail::class, function ($mail) use ($user) {
        return $mail->code === '123456' && $mail->verifyUrl === url('/verify-email/link/existing-token');
    });
});

test('new otp is created when none exists', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    
    Mail::fake();

    $response = $this->post('/resend-verification', [
        'email' => $user->email
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    // Should create new OTP
    $this->assertDatabaseCount('email_otps', 1);
    
    $otp = EmailOtp::where('user_id', $user->id)->first();
    expect($otp->code)->toHaveLength(6);
    expect($otp->link_token)->toHaveLength(40); // bin2hex(20) = 40 chars
    expect($otp->expires_at)->toBeGreaterThan(now());

    Mail::assertSent(VerifyOtpMail::class);
});

test('rate limiting works for resend verification', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    
    // First request should succeed
    $response1 = $this->post('/resend-verification', [
        'email' => $user->email
    ]);
    $response1->assertRedirect();

    // Second request within 1 minute should be rate limited
    $response2 = $this->post('/resend-verification', [
        'email' => $user->email
    ]);
    $response2->assertSessionHasErrors(['email']);
});

test('email verification fires event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $user->assignRole('user');
    
    $otp = EmailOtp::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'code' => '123456',
        'link_token' => 'test-token',
        'expires_at' => now()->addMinutes(15),
        'used' => false
    ]);

    // Verify via link
    $response = $this->get('/verify-email/link/test-token');
    
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Email verified. Please login.');

    Event::assertDispatched(UserEmailVerified::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });

    // Check that email is verified
    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('otp is marked as used after verification', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    
    $otp = EmailOtp::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'code' => '123456',
        'link_token' => 'test-token',
        'expires_at' => now()->addMinutes(15),
        'used' => false
    ]);

    $response = $this->get('/verify-email/link/test-token');
    
    $otp->refresh();
    expect($otp->used)->toBeTrue();
});

test('expired otp cannot be used', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    
    $otp = EmailOtp::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'code' => '123456',
        'link_token' => 'expired-token',
        'expires_at' => now()->subMinutes(1), // Expired
        'used' => false
    ]);

    $response = $this->get('/verify-email/link/expired-token');
    
    $response->assertStatus(403);
    $response->assertSee('Invalid or expired verification link');
});