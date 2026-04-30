<?php

use App\Models\Tip;
use App\Models\User;
use App\Services\TipWriteResult;
use App\Services\TipWriteService;

afterEach(function () {
    \Mockery::close();
});

function createTipFor(User $user, array $overrides = []): Tip
{
    return Tip::query()->create(array_merge([
        'user_id' => $user->id,
        'title' => '기존 제목',
        'content' => '<p>기존 본문</p>',
        'status' => 'draft',
        'visibility' => 'public',
    ], $overrides));
}

test('authenticated user can store a tip through the write service', function () {
    $user = User::factory()->create();
    $tip = createTipFor($user, ['title' => '저장된 글']);

    $writer = \Mockery::mock(TipWriteService::class);
    $writer->shouldReceive('create')
        ->once()
        ->withArgs(function ($actor, $attributes, $thumbnailFile, $tagsPayload, $draftKey) use ($user) {
            return $actor->is($user)
                && $attributes === [
                    'title' => '새 글 제목',
                    'content' => '<p>본문</p>',
                    'status' => 'draft',
                    'visibility' => 'public',
                ]
                && $thumbnailFile === null
                && $tagsPayload === '["laravel"]'
                && $draftKey === 'draft-123';
        })
        ->andReturn(new TipWriteResult($tip, '태그 경고'));

    $this->app->instance(TipWriteService::class, $writer);

    $response = $this
        ->actingAs($user)
        ->post(route('tip.store'), [
            'title' => '새 글 제목',
            'content' => '<p>본문</p>',
            'status' => 'draft',
            'visibility' => 'public',
            'tags' => '["laravel"]',
            'editor_draft_key' => 'draft-123',
        ]);

    $response->assertRedirect(route('tip.show', ['tip_id' => $tip->id]))
        ->assertSessionHas('warning', '태그 경고');
});

test('owner can update a tip through the patch route', function () {
    $owner = User::factory()->create();
    $tip = createTipFor($owner);

    $writer = \Mockery::mock(TipWriteService::class);
    $writer->shouldReceive('update')
        ->once()
        ->withArgs(function ($actor, $targetTip, $attributes, $thumbnailFile, $deleteThumbnail, $tagsPayload, $draftKey) use ($owner, $tip) {
            return $actor->is($owner)
                && $targetTip->is($tip)
                && $attributes === [
                    'title' => '수정된 제목',
                    'content' => '<p>수정 본문</p>',
                    'status' => 'published',
                    'visibility' => 'public',
                ]
                && $thumbnailFile === null
                && $deleteThumbnail === false
                && $tagsPayload === null
                && $draftKey === null;
        })
        ->andReturn(new TipWriteResult($tip));

    $this->app->instance(TipWriteService::class, $writer);

    $response = $this
        ->actingAs($owner)
        ->patch(route('tip.update', ['tip' => $tip]), [
            'title' => '수정된 제목',
            'content' => '<p>수정 본문</p>',
            'status' => 'published',
            'visibility' => 'public',
        ]);

    $response->assertRedirect(route('tip.show', ['tip_id' => $tip->id]));
});

test('non owner cannot update another users tip', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tip = createTipFor($owner);

    $writer = \Mockery::mock(TipWriteService::class);
    $writer->shouldNotReceive('update');
    $this->app->instance(TipWriteService::class, $writer);

    $response = $this
        ->actingAs($otherUser)
        ->patch(route('tip.update', ['tip' => $tip]), [
            'title' => '권한 없음',
            'content' => '<p>수정 시도</p>',
            'status' => 'draft',
            'visibility' => 'public',
        ]);

    $response->assertForbidden();
});

test('owner can delete a tip through the delete route', function () {
    $owner = User::factory()->create();
    $tip = createTipFor($owner);

    $writer = \Mockery::mock(TipWriteService::class);
    $writer->shouldReceive('delete')
        ->once()
        ->withArgs(fn ($actor, $targetTip) => $actor->is($owner) && $targetTip->is($tip));

    $this->app->instance(TipWriteService::class, $writer);

    $response = $this
        ->actingAs($owner)
        ->delete(route('tip.destroy', ['tip' => $tip]), [
            'submit_from' => 'front',
        ]);

    $response->assertRedirect(route('home'));
});
