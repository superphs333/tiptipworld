<?php

use App\Models\Tip;
use App\Models\User;
use App\Services\HomeViewService;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

function createHomeCategory(): int
{
    return (int) DB::table('categories')->insertGetId([
        'name' => '테스트 카테고리',
        'slug' => 'test-category-' . Str::random(8),
        'sort_order' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createHomeTag(string $name): int
{
    return (int) DB::table('tags')->insertGetId([
        'name' => $name,
        'slug' => Str::slug($name, '-') . '-' . Str::random(8),
        'type' => 'topic',
        'normalized_tag_id' => null,
        'usage_count' => 0,
        'is_blocked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createHomeTip(User $user, int $categoryId, array $overrides = []): Tip
{
    return Tip::query()->create(array_merge([
        'user_id' => $user->id,
        'category_id' => $categoryId,
        'title' => '홈 테스트 팁',
        'content' => '<p>홈 테스트 본문</p>',
        'status' => 'draft',
        'visibility' => 'public',
    ], $overrides));
}

function attachTagToTip(int $tipId, int $tagId): void
{
    DB::table('tip_tag')->insert([
        'tip_id' => $tipId,
        'tag_id' => $tagId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('popular tags count only published public tips', function () {
    $user = User::factory()->create();
    $categoryId = createHomeCategory();
    $tagId = createHomeTag('라라벨');

    $publicTip = createHomeTip($user, $categoryId, [
        'status' => 'published',
        'visibility' => 'public',
    ]);
    $draftTip = createHomeTip($user, $categoryId, [
        'status' => 'draft',
        'visibility' => 'public',
    ]);
    $privateTip = createHomeTip($user, $categoryId, [
        'status' => 'published',
        'visibility' => 'private',
    ]);

    attachTagToTip($publicTip->id, $tagId);
    attachTagToTip($draftTip->id, $tagId);
    attachTagToTip($privateTip->id, $tagId);

    $result = HomeViewService::getPopularTags();
    $tag = $result->firstWhere('id', $tagId);

    expect($tag)->not()->toBeNull();
    expect((int) $tag->tips_count)->toBe(1);
});

test('popular tags exclude tags without published public tips', function () {
    $user = User::factory()->create();
    $categoryId = createHomeCategory();
    $visibleTagId = createHomeTag('공개태그');
    $hiddenTagId = createHomeTag('비공개태그');

    $publicTip = createHomeTip($user, $categoryId, [
        'status' => 'published',
        'visibility' => 'public',
    ]);
    $draftTip = createHomeTip($user, $categoryId, [
        'status' => 'draft',
        'visibility' => 'public',
    ]);

    attachTagToTip($publicTip->id, $visibleTagId);
    attachTagToTip($draftTip->id, $hiddenTagId);

    $result = HomeViewService::getPopularTags();
    $tagIds = $result->pluck('id')->all();

    expect($tagIds)->toContain($visibleTagId);
    expect($tagIds)->not()->toContain($hiddenTagId);
});

test('hero stats are built from prepared category and tag collections', function () {
    $categories = new Collection([
        ['name' => '청소', 'tips_count' => 10],
        ['name' => '요리', 'tips_count' => 4],
    ]);

    $popularTags = new Collection([
        ['name' => '꿀팁', 'tips_count' => 7],
        ['name' => '정리', 'tips_count' => 3],
    ]);

    $stats = HomeViewService::getHeroStats($categories, $popularTags);

    expect($stats)->toMatchArray([
        'total_tips' => 14,
        'total_tips_text' => '14',
        'top_category' => [
            'name' => '청소',
            'count' => 10,
            'count_text' => '10',
        ],
        'top_tag' => [
            'name' => '#꿀팁',
            'count' => 7,
            'count_text' => '7',
        ],
    ]);
});
