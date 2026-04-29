<?php

use App\Models\User;
use App\Services\Media\EditorImageService;
use Illuminate\Http\UploadedFile;

afterEach(function () {
    \Mockery::close();
});

test('authenticated user can upload a summernote image', function () {
    $user = User::factory()->create();
    $draftKey = 'draft-session-123';
    $storedPath = sprintf(
        'media/posts/drafts/%d/%s/editor/summernote-sample-uuid.png',
        $user->id,
        $draftKey,
    );
    $imageUrl = sprintf(
        'https://cdn.example.com/media/posts/drafts/%d/%s/editor/summernote-sample-uuid.png',
        $user->id,
        $draftKey,
    );

    $editorImages = \Mockery::mock(EditorImageService::class);
    $editorImages->shouldReceive('store')
        ->once()
        ->withArgs(function ($actor, $image, $tip, $filename, $receivedDraftKey) use ($user, $draftKey) {
            return $actor->is($user)
                && $image instanceof UploadedFile
                && $tip === null
                && $filename === 'summernote-sample'
                && $receivedDraftKey === $draftKey;
        })
        ->andReturn($storedPath);
    $editorImages->shouldReceive('url')
        ->once()
        ->with($storedPath)
        ->andReturn($imageUrl);

    $this->app->instance(EditorImageService::class, $editorImages);

    $response = $this
        ->actingAs($user)
        ->post(route('summernote.uploadImage'), [
            'image' => UploadedFile::fake()->image('summernote-sample.png'),
            'draft_key' => $draftKey,
        ]);

    $response->assertOk()
        ->assertJson([
            'url' => $imageUrl,
            'alt' => 'summernote-sample',
        ]);
});
