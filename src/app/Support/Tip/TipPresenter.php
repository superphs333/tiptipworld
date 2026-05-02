<?php

namespace App\Support\Tip;

use App\Enums\TipStatus;
use App\Enums\TipVisibility;
use App\Models\Tip;
use Illuminate\Support\Str;

/**
 * Tip 모델을 화면에서 바로 쓰기 쉬운 배열 구조로 변환하는 Presenter
 * 
 * [목적]
 * - Blade/UI 컴포넌트가 Eloquent 모델 구조를 직접 해석하지 않도록 함
 * - 화면별로 필요한 필드만 정리된 배열 형태로 제공
 * - 라벨, URL, 포맷 문자열, 기본값 처리 같은 "표현형 로직"을 한곳이 모음 
 * 
 * [특징]
 * - 데이터 조회 자체는 하지 않고, 이미 로드된 Tip 모델을 화면용으로 가공
 * - 카드, 리스트, 관리자 행, 상세 화면 등 화면 종류에 따라 서로 다른 구조를 반환 
 */
final class TipPresenter
{
    /**
     * Tip 모델을 사용자 피드 카드 UI에서 바로 쓸 수 있는 배열 구조로 변환
     *      
     */
    public function presentCard(Tip $tip): array
    {
        return [
            'id' => (int) $tip->id,
            'title' => (string) $tip->title,
            'detail_url' => route('tip.show', ['tip_id' => $tip->id]),
            'thumbnail_url' => $this->thumbnailUrl($tip),
            'thumbnail_alt' => (string) $tip->title,
            'author' => $this->author($tip),
            'category' => $this->category($tip),
            'metrics' => $this->metrics($tip),
            'reaction' => $this->reaction($tip),
        ];
    }

    /**
     * 목록형 아이템 UI에서 사용할 배열 구조 만듦
     */
    public function presentListItem(Tip $tip): array
    {
        return array_merge($this->presentCard($tip), [
            'created_text' => $this->dateText($tip->created_at),
            'summary' => Str::limit(
                trim(strip_tags((string) ($tip->excerpt ?: $tip->content))),
                110,
                '...'
            ),
            'tags' => $this->tags($tip),
        ]);
    }

    /**
     * 작성자 본인용 목록 행(my tips row) 데이터 
     */
    public function presentOwnerRow(Tip $tip): array
    {
        return [
            'id' => (int) $tip->id,
            'title' => (string) $tip->title,
            'detail_url' => route('tip.show', ['tip_id' => $tip->id]),
            'thumbnail_url' => $this->thumbnailUrl($tip, false),
            'category' => $this->category($tip),
            'tags' => $this->tags($tip),
            'metrics' => $this->metrics($tip),
            'visibility' => $this->visibility($tip),
            'status' => $this->status($tip),
            'date_text' => $this->dateText($tip->created_at ?? $tip->updated_at, 'y-m-d A h:i'),
        ];
    }

    /**
     * 관리자 목록 행(admin row)에서 사용할 배열 구조 
     */
    public function presentAdminRow(Tip $tip): array
    {
        return [
            'id' => (int) $tip->id,
            'title' => (string) $tip->title,
            'edit_url' => route('admin.tip.form', ['tip' => $tip->id]),
            'delete_url' => route('tip.destroy', ['tip' => $tip->id]),
            'thumbnail_url' => $this->thumbnailUrl($tip, false),
            'author' => $this->author($tip),
            'category' => $this->category($tip),
            'tags' => $this->tags($tip),
            'visibility' => $this->visibility($tip),
            'status' => $this->status($tip),
            'date_text' => $this->dateText($tip->created_at ?? $tip->updated_at, 'y-m-d A h:i'),
            'updated_at_raw' => optional($tip->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * 상세 화면에서 사용할 팁 데이터 
     */
    public function presentDetail(Tip $tip, bool $canManage): array
    {
        $detailUrl = route('tip.show', ['tip_id' => $tip->id]);
        $likedUsers = $this->reactionUsers(data_get($tip, 'likedUsers', []));
        $bookmarkedUsers = $this->reactionUsers(data_get($tip, 'bookmarkedUsers', []));

        return [
            'id' => (int) $tip->id,
            'title' => (string) $tip->title,
            'detail_url' => $detailUrl,
            'category' => $this->category($tip),
            'status' => $this->status($tip),
            'visibility' => $this->visibility($tip),
            'author' => $this->author($tip),
            'created_text' => $this->dateText($tip->created_at, 'Y-m-d H시i분s초'),
            'thumbnail_url' => blank($tip->thumbnail) ? null : $this->thumbnailUrl($tip),
            'content_html' => (string) $tip->content,
            'has_content' => trim((string) $tip->content) !== '',
            'metrics' => $this->metrics($tip),
            'tags' => $this->tags($tip),
            'reaction' => $this->reaction($tip),
            'reactions' => [
                'likes' => $this->reactionGroup($likedUsers),
                'bookmarks' => $this->reactionGroup($bookmarkedUsers),
            ],
            'share' => [
                'title' => (string) $tip->title,
                'text' => $this->shareText((string) $tip->content),
                'url' => $detailUrl,
            ],
            'manage' => [
                'can_manage' => $canManage,
                'edit_url' => route('tip.formFront', ['tip' => $tip->id]),
                'delete_url' => route('tip.destroy', ['tip' => $tip->id]),
            ],
        ];
    }

    /**
     * 보관함(archive) 화면용 아이템 배열 
     */
    public function presentArchiveItem(Tip $tip, string $savedType): array
    {
        $card = $this->presentCard($tip);
        $tags = $this->tags($tip);

        return array_merge($card, [
            'saved_type' => $savedType,
            'filter' => [
                'category_value' => data_get($card, 'category.id', 0) > 0
                    ? (string) data_get($card, 'category.id')
                    : 'uncategorized',
                'tag_values' => collect($tags)
                    ->pluck('id')
                    ->filter(static fn ($tagId) => (int) $tagId > 0)
                    ->map(static fn ($tagId) => (string) $tagId)
                    ->values()
                    ->all(),
                'tag_values_text' => collect($tags)
                    ->pluck('id')
                    ->filter(static fn ($tagId) => (int) $tagId > 0)
                    ->implode('|'),
            ],
            'tags' => $tags,
        ]);
    }

    /**
     * 카테고리/태그별 팁 개수 집계 결과를 뷰용 배열 구조로 변환
     * 
     * [특징]
     * - 태그면 label 앞에 #를 붙임
     * - 숫자 ID가 아닌 경우 URL은 null이 될 수 있음
     */
    public function presentTipCountItem(array $item, bool $isTag = false): array
    {
        $id = data_get($item, 'id');
        $count = (int) data_get($item, 'tips_count', 0);
        $label = (string) data_get($item, 'name', $isTag ? '태그' : '미분류');

        return [
            'id' => $id,
            'name' => $label,
            'label' => $isTag ? '#' . $label : $label,
            'tips_count' => $count,
            'tips_count_text' => number_format($count),
            'url' => $this->countItemUrl($id, $isTag),
        ];
    }

    /**
     * 카드에 들어갈 작성자 정보를 배열로 만든다
     */
    private function author(Tip $tip): array
    {
        return [
            'id' => (int) data_get($tip, 'user.id', 0),
            'name' => (string) data_get($tip, 'user.name', '작성자 미상'),
            'avatar_url' => (string) data_get($tip, 'user.profile_image_url', asset('images/avatar-default.svg')),
        ];
    }

    /**
     * 카드에 들어갈 카테고리 정보를 배열로 만든다
     */
    private function category(Tip $tip): array
    {
        $categoryId = (int) data_get($tip, 'category.id', 0);

        return [
            'id' => $categoryId,
            'name' => (string) data_get($tip, 'category.name', '미분류'),
            'url' => $categoryId > 0
                ? route('tips.category', ['category_id' => $categoryId])
                : null,
        ];
    }

    /**
     * 카드에서 사용하는 수치 지표를 화면 표시용 문자열과 함께 만든다 
     */
    private function metrics(Tip $tip): array
    {
        $views = (int) data_get($tip, 'view_count', 0);
        $likes = (int) data_get($tip, 'like_count', 0);
        $comments = (int) data_get($tip, 'comment_count', 0);
        $bookmarks = (int) data_get($tip, 'bookmark_count', 0);

        return [
            'views' => $views,
            'views_text' => number_format($views),
            'likes' => $likes,
            'likes_text' => number_format($likes),
            'comments' => $comments,
            'comments_text' => number_format($comments),
            'bookmarks' => $bookmarks,
            'bookmarks_text' => number_format($bookmarks),
        ];
    }

    private function reactionGroup(array $users): array
    {
        $count = count($users);
        $previewLimit = 6;

        return [
            'count' => $count,
            'count_text' => number_format($count),
            'preview' => array_slice($users, 0, $previewLimit),
            'all' => $users,
            'has_items' => $count > 0,
            'remaining_count' => max($count - $previewLimit, 0),
            'remaining_count_text' => number_format(max($count - $previewLimit, 0)),
        ];
    }

    /**
     * 현재 조회자의 좋아요/북마크 여부를 boolean 형태로 정리
     */
    private function reaction(Tip $tip): array
    {
        return [
            'is_liked' => (int) data_get($tip, 'is_liked', 0) > 0,
            'is_bookmarked' => (int) data_get($tip, 'is_bookmarked', 0) > 0,
        ];
    }

    /**
     * 반응 사용자 목록을 화면 표시용 배열 구조로 정리 
     */
    private function reactionUsers(iterable $users): array
    {
        return collect($users)
            ->map(static function ($user) {
                $userId = (int) data_get($user, 'id', 0);

                return [
                    'id' => $userId,
                    'name' => (string) data_get($user, 'name', '이름 없음'),
                    'avatar_url' => (string) data_get($user, 'profile_image_url', asset('images/avatar-default.svg')),
                    'profile_url' => $userId > 0
                        ? route('tips.userFeed', ['user_id' => $userId])
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * 태그 목록을 화면 표시용 배열 구조로 정리 
     * 
     * [처리내용]
     * - 빈 이름 태그 제거
     * - label 앞에 # 추가
     * - 특정 태그명('삭제')은 경고성 표시용 is_alert 부여
     * - 유효한 tag_id가 있으면 태그 목록 URL 생성 
     */
    private function tags(Tip $tip): array
    {
        return collect(data_get($tip, 'tags', []))
            ->map(static function ($tag) {
                $tagId = (int) data_get($tag, 'id', 0);
                $tagName = trim((string) data_get($tag, 'name', ''));

                if ($tagName === '') {
                    return null;
                }

                return [
                    'id' => $tagId,
                    'name' => $tagName,
                    'label' => '#' . $tagName,
                    'is_alert' => $tagName === '삭제',
                    'url' => $tagId > 0
                        ? route('tips.tag', ['tag_id' => $tagId])
                        : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * visibility 값을 화면 표시용 메타 정보로 변환 
     */
    private function visibility(Tip $tip): array
    {
        $value = (string) data_get($tip, 'visibility', 'public');

        return [
            'key' => TipVisibility::keyFor($value),
            'label' => TipVisibility::labelFor($value),
            'tone' => TipVisibility::toneFor($value),
        ];
    }

    /**
     * status 값을 홤녀 표시용 메타 정보로 변환
     */
    private function status(Tip $tip): array
    {
        $value = (string) data_get($tip, 'status', 'draft');

        return [
            'key' => TipStatus::keyFor($value),
            'label' => TipStatus::labelFor($value),
            'tone' => TipStatus::toneFor($value),
        ];
    }

    /**
     * 썸네일 URL을 반환
     * (어떤 화면은 기본 썸네일 대체 이미지 쓰고, 어떤 화면은 없음 상태를 그대로 표현해야 하므로 이를 분기하기 위함)
     */
    private function thumbnailUrl(Tip $tip, bool $allowFallback = true): string
    {
        if (! $allowFallback && blank($tip->thumbnail)) {
            return '';
        }

        return (string) $tip->thumbnailUrl;
    }

    /**
     * 날짜/시간 값을 출력용 문자열로 포맷 
     * (값이 없으면 - 를 반환하여 뷰에서 null 처리를 반복하지 않게 함)
     */
    private function dateText(mixed $value, string $format = 'Y-m-d H:i'): string
    {
        return $value ? $value->format($format) : '-';
    }

    /**
     * 공유용 짧은 본문 요약 문자열 만들기 
     */
    private function shareText(string $content): string
    {
        $plain = trim(strip_tags($content));
        $firstSentence = preg_split('/[.!?]/u', $plain, 2)[0] ?? $plain;

        return mb_strimwidth((string) $firstSentence, 0, 120, '...');
    }

    /**
     * 집계 항목이 카테고리인지 태그인지에 따라 적절한 목록 페이지 url을 생성
     */
    private function countItemUrl(mixed $id, bool $isTag): ?string
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return $isTag
            ? route('tips.tag', ['tag_id' => (int) $id])
            : route('tips.category', ['category_id' => (int) $id]);
    }
}
