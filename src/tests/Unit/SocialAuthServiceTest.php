<?php

use App\Exceptions\SocialAuthException;
use App\Models\User;
use App\Services\Media\ProfileImageService;
use App\Services\SocialAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('profile_image_path')->nullable();
        $table->string('provider', 20)->default('email');
        $table->text('social_meta')->nullable();
        $table->string('social_id')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('users');
    \Mockery::close();
});

test('google social auth registers a new user and imports the remote avatar', function () {
    $profileImages = \Mockery::mock(ProfileImageService::class);
    $profileImages->shouldReceive('importFromUrl')
        ->once()
        ->withArgs(function (User $user, string $url, string $filename) {
            return $user->exists
                && $user->provider === 'google'
                && $user->social_id === 'google-123'
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
    expect($user->email)->toBe('googleuser@example.com');
    expect($user->name)->toBe('Google User');
    expect($user->provider)->toBe('google');
    expect($user->social_id)->toBe('google-123');
    expect($user->email_verified_at)->not->toBeNull();
    expect(json_decode((string) $user->social_meta, true))->toBe([
        'token' => 'google-token',
        'refreshToken' => 'google-refresh',
    ]);
});

test('kakao social auth links an existing email account without creating a duplicate user', function () {
    $existingUser = User::factory()->unverified()->create([
        'email' => 'member@example.com',
        'provider' => 'email',
        'social_id' => null,
        'social_meta' => null,
        'profile_image_path' => null,
    ]);

    $profileImages = \Mockery::mock(ProfileImageService::class);
    $profileImages->shouldReceive('importFromUrl')
        ->once()
        ->withArgs(function (User $user, string $url, string $filename) use ($existingUser) {
            return $user->is($existingUser)
                && $url === 'https://cdn.example.com/kakao-avatar.png'
                && $filename === 'kakao-profile';
        })
        ->andReturn('media/users/1/profile/kakao-profile-uuid.png');

    $this->app->instance(ProfileImageService::class, $profileImages);

    $resolvedUser = app(SocialAuthService::class)->resolve('kakao', makeSocialiteUser(
        id: 'kakao-456',
        email: 'member@example.com',
        name: 'Kakao Member',
        avatar: 'https://cdn.example.com/kakao-avatar.png',
        token: 'kakao-token',
        refreshToken: 'kakao-refresh',
    ));

    expect(User::count())->toBe(1);
    expect($resolvedUser->is($existingUser->fresh()))->toBeTrue();
    expect($resolvedUser->provider)->toBe('kakao');
    expect($resolvedUser->social_id)->toBe('kakao-456');
    expect($resolvedUser->email_verified_at)->toBeNull();
    expect(json_decode((string) $resolvedUser->social_meta, true))->toBe([
        'token' => 'kakao-token',
        'refreshToken' => 'kakao-refresh',
    ]);
});

test('social auth rejects linking an email that already belongs to another provider', function () {
    User::factory()->create([
        'email' => 'member@example.com',
        'provider' => 'kakao',
        'social_id' => 'kakao-999',
        'social_meta' => json_encode([
            'token' => 'existing-token',
            'refreshToken' => 'existing-refresh',
        ]),
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
        '이미 다른 소셜 계정과 연결된 이메일입니다. 기존 로그인 방식을 사용해 주세요.',
    );

    expect(User::count())->toBe(1);
    expect(User::first()?->provider)->toBe('kakao');
    expect(User::first()?->social_id)->toBe('kakao-999');
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
