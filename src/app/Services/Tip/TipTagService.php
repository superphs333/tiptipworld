<?php

namespace App\Services\Tip;

use App\Models\Tag;
use App\Models\Tip;

final class TipTagService
{
    /**
     * 글의 태그를 한 번에 정리해서 저장
     *
     * - 화면에서 넘어온 태그 값 정리 (공백 제거, #제거, 중복 제거)
     * - 금지 태그는 자동으로 제외
     * - 남은 태그만 글에 저장(sync)
     * - 제외된 금지 태그가 있으면 사용자 안내 문구 반환
     *
     * @param Tip $tip 태그를 저장할 글 모델
     * @param string|null $rawTags 화면에서 넘어온 태그 json 문자열
     * @return string|null 금지 태그 안내 문구(없으면 null)
     */
    public function syncTipTagsFromPayload(Tip $tip, ?string $rawTags): ?string
    {
        // 프론트에서 넘어온 tags(JSON 문자열)를 배열로 반환
        $decoded = json_decode((string) $rawTags, true);

        // 태그 이름 정리
        $tagNames = collect(is_array($decoded) ? $decoded : [])
            ->map(static fn ($tag) => ltrim(trim((string) $tag), '#')) // 문자열화, 앞뒤 공백 제거, 앞의 # 제거
            ->filter(static fn ($tag) => $tag !== '') // 빈 문자열 제거
            ->unique() // 중복제거
            ->values();

        // 입력 태그 중 DB에서 금지된 태그(is_blocked=1) 찾기
        $blockedTagNames = $tagNames->isEmpty()
            ? collect()
            : Tag::query()
                ->whereIn('name', $tagNames->all())
                ->where('is_blocked', true)
                ->pluck('name')
                ->map(static fn ($name) => trim((string) $name))
                ->filter(static fn ($name) => $name !== '')
                ->unique()
                ->values();

        // 금지 태그를 제외한 태그만 id로 변환
        $blockedTagMap = $blockedTagNames->flip();
        $tagIds = $tagNames
            ->reject(static fn ($tagName) => $blockedTagMap->has($tagName))
            ->map(static function ($tagName) {
                $tag = Tag::firstOrCreate(
                    ['name' => $tagName],
                    ['is_blocked' => false]
                );

                return (bool) $tag->is_blocked ? null : (int) $tag->id;
            })
            ->filter(static fn ($tagId) => $tagId !== null)
            ->unique()
            ->values()
            ->all();

        // tip_tag를 최종 목록으로 동기화
        $tip->allTags()->sync($tagIds);

        // 금지 태그가 없으면 안내문구 없음(null)
        if ($blockedTagNames->isEmpty()) {
            return null;
        }

        // 금지 태그가 있으면 사용자에게 보여줄 안내 문구 반환
        $tagLabel = $blockedTagNames
            ->map(static fn ($tagName) => '#' . $tagName)
            ->implode(', ');

        return "{$tagLabel} 태그는 사용할 수 없는 태그라 포함되지 않았습니다.";
    }
}
