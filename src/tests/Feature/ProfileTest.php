<?php

use App\Services\SocialAccountRevoker;
use App\Models\User;

afterEach(function () {
    \Mockery::close();
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('social user can delete their account without password', function () {
    $user = User::factory()->create();
    $user->socialAccounts()->create([
        'provider' => 'google',
        'provider_user_id' => 'google-user-1',
        'meta' => ['token' => 'social-token'],
    ]);

    $revoker = \Mockery::mock(SocialAccountRevoker::class);
    $revoker->shouldReceive('revoke')
        ->once()
        ->with(\Mockery::type(User::class))
        ->andReturn(true);

    $this->app->instance(SocialAccountRevoker::class, $revoker);

    $response = $this
        ->actingAs($user)
        ->delete('/profile');

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('social user account deletion is blocked when social unlink fails', function () {
    $user = User::factory()->create();
    $user->socialAccounts()->create([
        'provider' => 'google',
        'provider_user_id' => 'google-user-1',
        'meta' => ['token' => 'social-token'],
    ]);

    $revoker = \Mockery::mock(SocialAccountRevoker::class);
    $revoker->shouldReceive('revoke')
        ->once()
        ->with(\Mockery::type(User::class))
        ->andReturn(false);

    $this->app->instance(SocialAccountRevoker::class, $revoker);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile');

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'account')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
