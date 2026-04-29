<?php

use App\Models\Tip;
use App\Models\User;
use App\Services\Media\EditorImageService;
use App\Services\Media\ProfileImageService;
use App\Services\Media\TipThumbnailService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    \Mockery::close();
});

test('profile image service replaces the previous image after persisting the new one', function () {
    Storage::fake('r2');

    $oldPath = 'media/users/7/profile/old-profile.jpg';
    Storage::disk('r2')->put($oldPath, 'old-image');

    $user = \Mockery::mock(User::class)->makePartial();
    $user->id = 7;
    $user->profile_image_path = $oldPath;
    $user->shouldReceive('save')->once()->andReturnTrue();

    $service = app(ProfileImageService::class);

    $newPath = $service->replace($user, UploadedFile::fake()->image('avatar.png'));

    expect($user->profile_image_path)->toBe($newPath);
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('r2')->assertExists($newPath);
    Storage::disk('r2')->assertMissing($oldPath);
});

test('profile image service imports a remote image into the user profile path', function () {
    Storage::fake('r2');
    Http::fake([
        'https://example.com/*' => Http::response('remote-image', 200, [
            'Content-Type' => 'image/webp; charset=utf-8',
        ]),
    ]);

    $user = \Mockery::mock(User::class)->makePartial();
    $user->id = 9;
    $user->profile_image_path = null;
    $user->shouldReceive('save')->once()->andReturnTrue();

    $service = app(ProfileImageService::class);

    $path = $service->importFromUrl($user, 'https://example.com/avatar', 'google-profile');

    expect($path)->not->toBeNull();
    expect(str_starts_with((string) $path, 'media/users/9/profile/google-profile-'))->toBeTrue();
    Storage::disk('r2')->assertExists((string) $path);
    expect($service->url(null))->toBe(asset('images/avatar-default.svg'));
});

test('tip thumbnail service stores files under the tip thumbnail prefix', function () {
    Storage::fake('r2');

    $tip = new Tip();
    $tip->id = 33;

    $service = app(TipThumbnailService::class);

    $path = $service->store($tip, UploadedFile::fake()->image('cover.png'));

    expect(str_starts_with($path, 'media/posts/33/thumbnails/cover-'))->toBeTrue();
    Storage::disk('r2')->assertExists($path);
    expect($service->url(null))->toBe(asset('images/no-thumbnail.png'));
});

test('editor image service stores draft images under the actor draft prefix', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 15;
    $draftKey = 'draft-session-123';

    $service = app(EditorImageService::class);

    $path = $service->store($actor, UploadedFile::fake()->image('inline.png'), null, 'inline-image', $draftKey);

    expect(str_starts_with($path, 'media/posts/drafts/15/draft-session-123/editor/inline-image-'))->toBeTrue();
    Storage::disk('r2')->assertExists($path);
});

test('editor image service stores tip images only for an authorized actor', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 44;
    $tip->user_id = 21;

    $service = app(EditorImageService::class);

    $path = $service->store($actor, UploadedFile::fake()->image('inline.png'), $tip, 'inline-image');

    expect(str_starts_with($path, 'media/posts/44/editor/inline-image-'))->toBeTrue();
    Storage::disk('r2')->assertExists($path);
});

test('editor image service rejects uploads for tips owned by another user', function () {
    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 45;
    $tip->user_id = 99;

    $service = app(EditorImageService::class);

    expect(fn () => $service->store(
        $actor,
        UploadedFile::fake()->image('inline.png'),
        $tip,
        'inline-image',
    ))->toThrow(AuthorizationException::class);
});

test('editor image service relocates draft images into the saved tip directory', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 44;
    $tip->user_id = 21;
    $draftKey = 'draft-session-123';

    $draftPath = 'media/posts/drafts/21/draft-session-123/editor/inline-image-1234.png';
    Storage::disk('r2')->put($draftPath, 'image-binary');

    $service = app(EditorImageService::class);
    $draftUrl = $service->url($draftPath);
    $content = sprintf('<p>before</p><img src="%s" alt=""><p>after</p>', $draftUrl);

    $relocatedContent = $service->relocateDraftImages($actor, $tip, $content, $draftKey);
    $targetPath = 'media/posts/44/editor/inline-image-1234.png';
    $targetUrl = $service->url($targetPath);

    expect($relocatedContent)->toContain($targetUrl);
    expect($relocatedContent)->not->toContain($draftUrl);
    Storage::disk('r2')->assertMissing($draftPath);
    Storage::disk('r2')->assertExists($targetPath);
});

test('editor image service cleans up only the current draft directory', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 44;
    $tip->user_id = 21;
    $draftKey = 'draft-session-123';
    $otherDraftKey = 'draft-session-999';

    $usedDraftPath = 'media/posts/drafts/21/draft-session-123/editor/inline-image-1234.png';
    $unusedDraftPath = 'media/posts/drafts/21/draft-session-123/editor/unused-image-9999.png';
    $otherDraftPath = 'media/posts/drafts/21/draft-session-999/editor/other-image-8888.png';

    Storage::disk('r2')->put($usedDraftPath, 'image-binary');
    Storage::disk('r2')->put($unusedDraftPath, 'unused-image-binary');
    Storage::disk('r2')->put($otherDraftPath, 'other-draft-image-binary');

    $service = app(EditorImageService::class);
    $content = sprintf('<p>before</p><img src="%s" alt=""><p>after</p>', $service->url($usedDraftPath));

    $relocatedContent = $service->relocateDraftImages($actor, $tip, $content, $draftKey);
    $targetPath = 'media/posts/44/editor/inline-image-1234.png';
    $targetUrl = $service->url($targetPath);

    expect($relocatedContent)->toContain($targetUrl);
    Storage::disk('r2')->assertExists($targetPath);
    Storage::disk('r2')->assertMissing($usedDraftPath);
    Storage::disk('r2')->assertMissing($unusedDraftPath);
    Storage::disk('r2')->assertExists($otherDraftPath);
});

test('editor image service deletes tip images removed from the updated content', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 44;
    $tip->user_id = 21;

    $keptPath = 'media/posts/44/editor/keep-image-1234.png';
    $removedPath = 'media/posts/44/editor/remove-image-9999.png';
    Storage::disk('r2')->put($keptPath, 'keep-image-binary');
    Storage::disk('r2')->put($removedPath, 'remove-image-binary');

    $service = app(EditorImageService::class);
    $previousContent = sprintf(
        '<p>before</p><img src="%s" alt=""><img src="%s" alt=""><p>after</p>',
        $service->url($keptPath),
        $service->url($removedPath),
    );
    $currentContent = sprintf('<p>before</p><img src="%s" alt=""><p>after</p>', $service->url($keptPath));

    $service->deleteRemovedTipImages($actor, $tip, $previousContent, $currentContent);

    Storage::disk('r2')->assertExists($keptPath);
    Storage::disk('r2')->assertMissing($removedPath);
});

test('editor image service deletes all tip images', function () {
    Storage::fake('r2');

    $actor = \Mockery::mock(User::class)->makePartial();
    $actor->id = 21;
    $actor->shouldReceive('isAdmin')->once()->andReturnFalse();

    $tip = new Tip();
    $tip->id = 44;
    $tip->user_id = 21;

    $firstPath = 'media/posts/44/editor/first-image-1234.png';
    $secondPath = 'media/posts/44/editor/second-image-9999.png';
    Storage::disk('r2')->put($firstPath, 'first-image-binary');
    Storage::disk('r2')->put($secondPath, 'second-image-binary');

    $service = app(EditorImageService::class);
    $service->deleteAllTipImages($actor, $tip);

    Storage::disk('r2')->assertMissing($firstPath);
    Storage::disk('r2')->assertMissing($secondPath);
});
