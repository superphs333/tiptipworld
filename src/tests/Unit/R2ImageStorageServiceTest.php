<?php

use App\Services\Media\MediaPath;
use App\Services\Media\R2ImageStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('stores uploaded profile images under the configured media path', function () {
    Storage::fake('r2');

    $images = app(R2ImageStorageService::class);

    $path = $images->store(
        UploadedFile::fake()->image('avatar.jpg'),
        MediaPath::userProfile(7),
        'profile',
    );

    expect(str_starts_with($path, 'media/users/7/profile/profile-'))->toBeTrue();
    expect(str_ends_with($path, '.jpg'))->toBeTrue();
    Storage::disk('r2')->assertExists($path);
    expect($images->url($path))->toBe(Storage::disk('r2')->url($path));
});

test('deletes stored images from r2', function () {
    Storage::fake('r2');

    $images = app(R2ImageStorageService::class);
    $path = $images->store(
        UploadedFile::fake()->image('thumbnail.png'),
        MediaPath::postThumbnails(99),
        'cover',
    );

    Storage::disk('r2')->assertExists($path);
    $images->delete($path);
    Storage::disk('r2')->assertMissing($path);
});

test('stores remote images under the configured media path', function () {
    Storage::fake('r2');
    Http::fake([
        'https://example.com/*' => Http::response('image-binary', 200, [
            'Content-Type' => 'image/webp; charset=utf-8',
        ]),
    ]);

    $images = app(R2ImageStorageService::class);

    $path = $images->storeFromUrl(
        'https://example.com/avatar',
        MediaPath::postEditor(10),
        'summernote-image',
    );

    expect($path)->not->toBeNull();
    expect(str_starts_with((string) $path, 'media/posts/10/editor/summernote-image-'))->toBeTrue();
    expect(str_ends_with((string) $path, '.webp'))->toBeTrue();
    Storage::disk('r2')->assertExists((string) $path);
});

test('moves stored images within r2', function () {
    Storage::fake('r2');

    $images = app(R2ImageStorageService::class);
    $sourcePath = 'media/posts/drafts/7/draft-session-123/editor/example-image.png';
    $targetPath = 'media/posts/44/editor/example-image.png';

    Storage::disk('r2')->put($sourcePath, 'image-binary');

    $images->move($sourcePath, $targetPath);

    Storage::disk('r2')->assertMissing($sourcePath);
    Storage::disk('r2')->assertExists($targetPath);
});
