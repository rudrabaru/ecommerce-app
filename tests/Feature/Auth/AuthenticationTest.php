<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->update([
        'email_verified_at' => now(),
        'account_verified_at' => now()
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users with unverified email cannot login', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->update(['email_verified_at' => null, 'account_verified_at' => now()]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Please verify your email address to continue.']);
});

test('users with unverified account cannot login', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->update(['email_verified_at' => now(), 'account_verified_at' => null]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Your account is currently under review or has been disabled by an administrator.']);
});

test('providers with unverified email cannot login', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $user->update(['email_verified_at' => null, 'account_verified_at' => now()]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Please verify your email address to continue.']);
});

test('providers with unverified account cannot login', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $user->update(['email_verified_at' => now(), 'account_verified_at' => null]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Your account is currently under review or has been disabled by an administrator.']);
});

test('admins can login without verification checks', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->update(['email_verified_at' => null, 'account_verified_at' => null]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('role changes do not affect verification status', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->update(['email_verified_at' => now(), 'account_verified_at' => now()]);

    // Change role to provider
    $user->syncRoles(['provider']);
    $user->refresh();

    // Should still be able to login
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});
