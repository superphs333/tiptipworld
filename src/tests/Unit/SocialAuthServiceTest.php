<?php

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Media\ProfileImageService;
use App\Services\SocialAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('social_accounts');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('profile_image_path')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('social_accounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('provider', 20);
        $table->string('provider_user_id');
        $table->json('meta')->nullable();
        $table->timestamps();
        $table->unique(['provider', 'provider_user_id'], 'social_accounts_provider_user_unique');
        $table->unique(['user_id', 'provider'], 'social_accounts_user_provider_unique');
    });
});

afterEach(function () {
    Schema::dropIfExists('social_accounts');
    Schema::dropIfExists('users');
    \Mockery::close();
});

test('google social auth registers a new user and imports the remote avatar', function () {
    $profileImages = \Mockery::mock(ProfileImageService::class);
    $profileImages->shouldReceive('importFromUrl')
        ->once()
        ->withArgs(function (User $user, string $url, string $filename) {
            return $user->exists
                && $user->socialAccounts()->where('provider', 'google')->where('provider_user_id', 'google-123')->exists()
                && $url === 'https://cdn.example.com/google-avatar.png'
                && $filename === 'google-profile';
        })
        ->andReturn('media/users/1/profile/google-profile-uuid.png');

    $this->app->instance(ProfileImageService::class, $profileImages);

    $user = app(SocialAuthService::class)->resolve('google', makeSocialiteUser(
        id: 'google-123',
        email: 'GoogleUser@example.com',
        name: 'Google User',
        avatar: 'https://cdn.example.com/google-avatar.png',
        token: 'google-token',
        refreshToken: 'google-refresh',
    ));

    expect(User::count())->toBe(1);
    expect(SocialAccount::count())->toBe(1);
    expect($user->email)->toBe('googleuser@example.com');
    expect($user->name)->toBe('Google User');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->socialAccounts()->first()?->provider)->toBe('google');
    expect($user->socialAccounts()->first()?->provider_user_id)->toBe('google-123');
    expect($user->socialAccounts()->first()?->meta)->toBe([
        'token' => 'google-token',
        'refreshToken' => 'google-refresh',
    ]);
});

test('kakao social auth stops and notifies when the email already belongs to an existing account', function () {
    $existingUser = User::factory()->unverified()->create([
        'email' => 'member@example.com',
        'profile_image_path' => null,
    ]);

    $profileImages = \Mockery::mock(ProfileImageService::class);
    $profileImages->shouldNotReceive('importFromUrl');

    $this->app->instance(ProfileImageService::class, $profileImages);

    expect(fn () => app(SocialAuthService::class)->resolve('kakao', makeSocialiteUser(
        id: 'kakao-456',
        email: 'member@example.com',
        name: 'Kakao Member',
        avatar: 'https://cdn.example.com/kakao-avatar.png',
        token: 'kakao-token',
        refreshToken: 'kakao-refresh',
    )))->toThrow(
        SocialAuthException::class,
        '같은 이메일의 기존 계정이 있습니다. 기존 로그인 방식으로 로그인해 주세요.',
    );

    expect(User::count())->toBe(1);
    expect($existingUser->fresh()?->email_verified_at)->toBeNull();
    expect(SocialAccount::count())->toBe(0);
});

test('social auth stops and notifies when the email already belongs to another provider account', function () {
    $existingUser = User::factory()->create([
        'email' => 'member@example.com',
    ]);

    $existingUser->socialAccounts()->create([
        'provider' => 'kakao',
        'provider_user_id' => 'kakao-999',
        'meta' => [
            'token' => 'existing-token',
            'refreshToken' => 'existing-refresh',
        ],
    ]);

    $profileImages = \Mockery::mock(ProfileImageService::class);
    $profileImages->shouldNotReceive('importFromUrl');

    $this->app->instance(ProfileImageService::class, $profileImages);

    expect(fn () => app(SocialAuthService::class)->resolve('google', makeSocialiteUser(
        id: 'google-222',
        email: 'member@example.com',
        name: 'Google Member',
        avatar: 'https://cdn.example.com/google-avatar.png',
        token: 'google-token',
        refreshToken: 'google-refresh',
    )))->toThrow(
        SocialAuthException::class,
        '같은 이메일의 기존 계정이 있습니다. 기존 로그인 방식으로 로그인해 주세요.',
    );

    expect(User::count())->toBe(1);
    expect(SocialAccount::count())->toBe(1);
    expect(SocialAccount::first()?->provider)->toBe('kakao');
    expect(SocialAccount::first()?->provider_user_id)->toBe('kakao-999');
});

function makeSocialiteUser(
    string $id,
    ?string $email,
    ?string $name,
    ?string $avatar,
    string $token,
    ?string $refreshToken = null,
): SocialiteUser {
    return (new SocialiteUser())
        ->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar' => $avatar,
        ])
        ->setToken($token)
        ->setRefreshToken($refreshToken);
}
