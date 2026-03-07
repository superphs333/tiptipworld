<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $blueprints = $this->categoryTipBlueprints();

            $categories = Category::query()
                ->whereIn('name', array_keys($blueprints))
                ->get()
                ->keyBy('name');

            $authors = User::query()
                ->whereIn('email', $this->authorEmails())
                ->orderBy('id')
                ->get()
                ->values();

            if ($authors->isEmpty()) {
                $authors = User::query()->orderBy('id')->get()->values();
            }

            if ($authors->isEmpty()) {
                return;
            }

            $allUserIds = User::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $authorCount = $authors->count();
            $categoryIndex = 0;

            foreach ($blueprints as $categoryName => $payload) {
                $category = $categories->get($categoryName);
                if (! $category instanceof Category) {
                    $categoryIndex++;
                    continue;
                }

                foreach ($payload['tips'] as $tipIndex => $tipData) {
                    $author = $authors[($categoryIndex + $tipIndex) % $authorCount];
                    $tagNames = array_values(array_unique(array_merge(
                        $payload['base_tags'],
                        $tipData['tags']
                    )));
                    $tagIds = $this->resolveTagIds($tagNames);
                    $publishedAt = now()->subDays(($categoryIndex * 10) + $tipIndex + 1);

                    $tip = Tip::query()->updateOrCreate(
                        [
                            'category_id' => (int) $category->id,
                            'title' => $tipData['title'],
                        ],
                        [
                            'user_id' => (int) $author->id,
                            'update_user_id' => (int) $author->id,
                            'excerpt' => $tipData['excerpt'],
                            'content' => $this->buildContent(
                                $categoryName,
                                $tipData['title'],
                                $tipData['excerpt'],
                                $tagNames
                            ),
                            'status' => 'published',
                            'visibility' => 'public',
                            'published_at' => $publishedAt,
                            'tags_count' => 0,
                            'view_count' => 120 + ($categoryIndex * 40) + ($tipIndex * 11),
                            'like_count' => 0,
                            'bookmark_count' => 0,
                            'comment_count' => 2 + (($categoryIndex + $tipIndex) % 9),
                        ]
                    );

                    $tip->allTags()->sync($tagIds);

                    $reactionPool = array_values(array_filter(
                        $allUserIds,
                        static fn (int $userId): bool => $userId !== (int) $author->id
                    ));

                    $likeTarget = $this->determineLikeTarget(count($reactionPool), $categoryIndex, $tipIndex);
                    $likeUserIds = $this->pickUserIds(
                        $reactionPool,
                        $likeTarget,
                        ($categoryIndex * 13) + ($tipIndex * 7)
                    );
                    $tip->likedUsers()->sync($likeUserIds);

                    $bookmarkTarget = $this->determineBookmarkTarget(count($likeUserIds), $categoryIndex, $tipIndex);
                    $bookmarkUserIds = $this->pickUserIds(
                        $likeUserIds,
                        $bookmarkTarget,
                        ($categoryIndex * 5) + ($tipIndex * 3)
                    );
                    $tip->bookmarkedUsers()->sync($bookmarkUserIds);

                    $tip->forceFill([
                        'tags_count' => count($tagIds),
                        'like_count' => count($likeUserIds),
                        'bookmark_count' => count($bookmarkUserIds),
                    ])->save();
                }

                $categoryIndex++;
            }
        });
    }

    private function resolveTagIds(array $tagNames): array
    {
        $ids = [];

        foreach ($tagNames as $tagName) {
            $tag = Tag::query()->firstOrCreate(
                ['name' => $tagName],
                ['is_blocked' => false]
            );

            if ((bool) $tag->is_blocked) {
                $tag->is_blocked = false;
                $tag->save();
            }

            $ids[] = (int) $tag->id;
        }

        return $ids;
    }

    private function determineLikeTarget(int $reactorCount, int $categoryIndex, int $tipIndex): int
    {
        if ($reactorCount <= 0) {
            return 0;
        }

        $target = 8 + (($categoryIndex * 5 + $tipIndex * 2) % 18);

        return min($reactorCount, max(1, $target));
    }

    private function determineBookmarkTarget(int $likeCount, int $categoryIndex, int $tipIndex): int
    {
        if ($likeCount <= 0) {
            return 0;
        }

        $target = intdiv($likeCount, 2) + (($categoryIndex + $tipIndex) % 3);

        return min($likeCount, max(1, $target));
    }

    private function pickUserIds(array $pool, int $targetCount, int $offsetSeed): array
    {
        $pool = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $pool)));
        $total = count($pool);

        if ($total === 0 || $targetCount <= 0) {
            return [];
        }

        $targetCount = min($targetCount, $total);
        $offset = $offsetSeed % $total;
        $ordered = array_merge(
            array_slice($pool, $offset),
            array_slice($pool, 0, $offset),
        );

        return array_slice($ordered, 0, $targetCount);
    }

    private function buildContent(string $categoryName, string $title, string $excerpt, array $tags): string
    {
        $steps = $this->categorySteps()[$categoryName] ?? [
            '실행 시간을 먼저 정하고 시작합니다.',
            '핵심 동작을 먼저 끝낸 뒤 디테일을 보완합니다.',
            '다음 실행 날짜를 기록해 습관으로 고정합니다.',
        ];

        $stepHtml = '';
        foreach ($steps as $step) {
            $stepHtml .= '<li>' . $step . '</li>';
        }

        $tagLine = implode(' ', array_map(static fn (string $tag): string => '#' . $tag, $tags));

        return <<<HTML
<h3>{$title}</h3>
<p>{$excerpt}</p>
<p>아래 순서를 기준으로 바로 적용해 보세요.</p>
<ol>
{$stepHtml}
</ol>
<p>{$tagLine}</p>
HTML;
    }

    private function categorySteps(): array
    {
        return [
            '청소' => [
                '오염이 심한 구역부터 10분 타이머로 시작합니다.',
                '한 번에 하나의 표면만 끝내서 완료감을 만듭니다.',
                '청소 도구를 제자리에 두어 다음 청소 시간을 줄입니다.',
            ],
            '피부' => [
                '세안 강도보다 자극 최소화에 우선순위를 둡니다.',
                '새 제품은 한 번에 하나씩 추가해 반응을 확인합니다.',
                '2주 단위로 피부 컨디션을 기록해 루틴을 조정합니다.',
            ],
            '생활' => [
                '반복되는 행동을 같은 시간대에 고정합니다.',
                '결정이 필요한 일은 전날 밤에 미리 정해둡니다.',
                '알림은 최소화하되 중요한 일정만 남겨 유지합니다.',
            ],
            '집안일' => [
                '주 1회 고정 작업과 매일 작업을 분리합니다.',
                '도구별 위치를 정해 찾는 시간을 줄입니다.',
                '완료 기준을 작게 잡아 미루는 습관을 줄입니다.',
            ],
            '요리' => [
                '재료 준비를 먼저 끝내 조리 시간을 단축합니다.',
                '양념 비율을 메모해 같은 맛을 재현합니다.',
                '남은 재료는 다음 끼니 메뉴와 바로 연결합니다.',
            ],
            '건강' => [
                '하루 1개 습관만 먼저 고정해 과부하를 막습니다.',
                '체감 변화를 기록해 동기보다 데이터를 믿습니다.',
                '무리한 계획보다 주 5일 실행 가능한 강도로 조정합니다.',
            ],
            '재테크' => [
                '고정비를 먼저 확인하고 변동비를 배분합니다.',
                '지출 기록은 항목 3개만 나눠 빠르게 유지합니다.',
                '자동이체와 결제일을 통합해 누락을 방지합니다.',
            ],
            '디지털' => [
                '알림과 권한 설정부터 정리해 피로를 줄입니다.',
                '자주 쓰는 앱만 첫 화면에 남겨 탐색 시간을 줄입니다.',
                '월 1회 디지털 정리일을 지정해 데이터 누적을 관리합니다.',
            ],
            '자취' => [
                '월세, 식비, 생활용품 예산을 분리해 관리합니다.',
                '주 단위 장보기로 냉장고 회전율을 유지합니다.',
                '비상상황 대비 물품을 고정 위치에 보관합니다.',
            ],
            '여행' => [
                '출발 3일 전 체크리스트로 준비를 마무리합니다.',
                '이동 시간과 휴식 시간을 함께 계획해 피로를 줄입니다.',
                '귀가 후 정리 루틴까지 포함해 여행 일정을 완성합니다.',
            ],
        ];
    }

    private function authorEmails(): array
    {
        return [
            'test@example.com',
            'minji@example.com',
            'jihoon@example.com',
            'seoyeon@example.com',
            'doyoon@example.com',
            'haeun@example.com',
            'yujin@example.com',
            'hyunwoo@example.com',
            'sua@example.com',
            'junseo@example.com',
        ];
    }

    private function categoryTipBlueprints(): array
    {
        return [
            '청소' => [
                'base_tags' => ['청소', '정리', '루틴'],
                'tips' => [
                    ['title' => '현관 먼지 5분 리셋 루틴', 'excerpt' => '출입이 잦은 현관을 매일 5분만 관리해 먼지 누적을 막는 방법', 'tags' => ['현관청소', '먼지관리']],
                    ['title' => '욕실 물때 주 2회 관리법', 'excerpt' => '샤워 후 2분 습관으로 욕실 물때가 쌓이지 않게 유지하는 방법', 'tags' => ['욕실청소', '물때제거']],
                    ['title' => '주방 기름때 안 쌓이게 닦는 순서', 'excerpt' => '요리 직후 닦는 포인트를 정해 찌든때로 번지지 않게 하는 방법', 'tags' => ['주방청소', '기름때']],
                    ['title' => '창틀 곰팡이 번지기 전에 정리하는 법', 'excerpt' => '환기와 건조 타이밍을 맞춰 창틀 곰팡이를 예방하는 방법', 'tags' => ['창틀청소', '곰팡이관리']],
                    ['title' => '침구 먼지 줄이는 세탁·건조 주기', 'excerpt' => '이불과 베개 커버를 주기적으로 관리해 먼지와 냄새를 줄이는 팁', 'tags' => ['침구관리', '먼지알레르기']],
                    ['title' => '냉장고 선반 냄새 없이 청소하기', 'excerpt' => '칸별 청소 순서와 보관 용기 교체로 냉장고 냄새를 줄이는 팁', 'tags' => ['냉장고정리', '탈취']],
                    ['title' => '로봇청소기 성능 유지하는 관리 팁', 'excerpt' => '브러시와 필터를 짧은 주기로 점검해 흡입력을 유지하는 방법', 'tags' => ['청소도구', '로봇청소기']],
                    ['title' => '전자레인지 찌든때 3분 스팀 청소', 'excerpt' => '물컵 스팀과 마른천만으로 전자레인지 내부를 빠르게 정리하는 방법', 'tags' => ['전자레인지', '스팀청소']],
                    ['title' => '반려동물 털 빠른 청소 동선 만들기', 'excerpt' => '털이 많이 모이는 지점을 기준으로 청소 동선을 고정하는 팁', 'tags' => ['반려동물청소', '털관리']],
                    ['title' => '청소도구 보관 위치만 바꿔도 쉬워지는 정리법', 'excerpt' => '도구 접근성을 높여 청소 시작 장벽을 낮추는 배치 방법', 'tags' => ['수납정리', '청소동선']],
                ],
            ],
            '피부' => [
                'base_tags' => ['피부관리', '보습', '저자극'],
                'tips' => [
                    ['title' => '아침 3단계 저자극 스킨케어 루틴', 'excerpt' => '세안, 보습, 자외선 차단 3단계만으로 아침 피부 장벽을 지키는 루틴', 'tags' => ['아침케어', '선크림']],
                    ['title' => '마스크 트러블 줄이는 세안 타이밍', 'excerpt' => '외출 후 세안 시점을 조절해 마스크 자극으로 인한 트러블을 줄이는 팁', 'tags' => ['트러블케어', '세안습관']],
                    ['title' => '건조한 날 각질 들뜸 막는 보습 순서', 'excerpt' => '수분층과 유분층을 차례로 쌓아 각질 들뜸을 완화하는 방법', 'tags' => ['건성피부', '각질관리']],
                    ['title' => '여름 피지 폭발 막는 저녁 관리법', 'excerpt' => '과한 세정 없이 피지 균형을 맞추는 저녁 루틴 구성법', 'tags' => ['피지관리', '여름피부']],
                    ['title' => '손 안 대고 블랙헤드 관리하는 습관', 'excerpt' => '압출 대신 온도와 보습 관리로 모공 자극을 줄이는 방법', 'tags' => ['모공관리', '블랙헤드']],
                    ['title' => '운동 후 피부 진정 루틴', 'excerpt' => '운동 직후 열감과 땀 자극을 빠르게 진정시키는 단계별 팁', 'tags' => ['운동후케어', '진정관리']],
                    ['title' => '선크림 덧바르기 쉬운 메이크업 팁', 'excerpt' => '화장이 뭉치지 않도록 자외선 차단제를 덧바르는 방법', 'tags' => ['자외선차단', '메이크업']],
                    ['title' => '야근 다음 날 붓기 줄이는 쿨링 케어', 'excerpt' => '아침 냉찜질과 순환 루틴으로 얼굴 붓기를 빠르게 완화하는 팁', 'tags' => ['붓기관리', '쿨링케어']],
                    ['title' => '민감성 피부 새 제품 테스트 방법', 'excerpt' => '패치 테스트와 도입 간격을 지켜 자극 리스크를 낮추는 방법', 'tags' => ['민감성피부', '패치테스트']],
                    ['title' => '피부 컨디션 기록으로 화장품 줄이는 법', 'excerpt' => '기록 데이터를 바탕으로 꼭 필요한 제품만 남기는 정리법', 'tags' => ['화장품정리', '피부기록']],
                ],
            ],
            '생활' => [
                'base_tags' => ['생활팁', '시간관리', '루틴'],
                'tips' => [
                    ['title' => '아침 준비시간 20분 줄이는 전날 루틴', 'excerpt' => '옷, 가방, 일정 확인을 전날로 옮겨 아침 혼잡을 줄이는 방법', 'tags' => ['아침준비', '출근루틴']],
                    ['title' => '집 열쇠·지갑 잃어버림 줄이는 고정 자리', 'excerpt' => '현관 앞 고정 수납으로 분실 스트레스를 줄이는 간단한 습관', 'tags' => ['분실방지', '현관정리']],
                    ['title' => '택배 박스 빠르게 정리하는 3단계', 'excerpt' => '개봉, 분리, 배출 순서를 고정해 택배 쓰레기 누적을 막는 방법', 'tags' => ['택배정리', '분리배출']],
                    ['title' => '공과금 납부일 안 놓치는 캘린더 습관', 'excerpt' => '월간 반복 알림으로 납부 누락과 연체를 예방하는 방법', 'tags' => ['공과금', '캘린더']],
                    ['title' => '비 오는 날 옷과 신발 관리법', 'excerpt' => '젖은 의류와 신발을 빠르게 건조해 냄새와 변형을 줄이는 팁', 'tags' => ['우천관리', '신발관리']],
                    ['title' => '집중 안 될 때 25분 타이머 활용법', 'excerpt' => '짧은 집중과 휴식 반복으로 할 일 착수 난도를 낮추는 방법', 'tags' => ['집중력', '포모도로']],
                    ['title' => '집안 냄새 줄이는 환기 시간표', 'excerpt' => '시간대별 환기와 흡습 포인트로 실내 공기 질을 높이는 팁', 'tags' => ['환기', '냄새관리']],
                    ['title' => '장보기 전에 냉장고 체크리스트 만드는 법', 'excerpt' => '중복 구매를 줄여 식비와 음식물 쓰레기를 동시에 줄이는 방법', 'tags' => ['장보기', '냉장고체크']],
                    ['title' => '일주일 계획을 무리 없이 세우는 기준', 'excerpt' => '우선순위 3개만 고정해 계획 피로를 줄이는 실전 방식', 'tags' => ['주간계획', '우선순위']],
                    ['title' => '밤에 잠 잘 오게 하는 취침 1시간 루틴', 'excerpt' => '불빛과 자극을 줄여 자연스럽게 수면 리듬을 맞추는 방법', 'tags' => ['수면루틴', '야간습관']],
                ],
            ],
            '집안일' => [
                'base_tags' => ['집안일', '정리', '실전팁'],
                'tips' => [
                    ['title' => '세탁물 색상 분리 기준 쉽게 정리하기', 'excerpt' => '흰색, 진색, 수건류 기준으로 세탁 실패를 줄이는 분류법', 'tags' => ['세탁', '분류정리']],
                    ['title' => '수건 냄새 안 나게 말리는 위치와 시간', 'excerpt' => '수건 섬유 냄새를 줄이는 건조 위치와 교체 주기 가이드', 'tags' => ['수건관리', '건조팁']],
                    ['title' => '빨래 건조대 공간 두 배 쓰는 배치법', 'excerpt' => '옷 길이와 두께 기준으로 건조대 효율을 높이는 방법', 'tags' => ['건조대', '공간활용']],
                    ['title' => '행주·수세미 위생적으로 교체하는 주기', 'excerpt' => '주방 소모품 교체 주기를 정해 위생 리스크를 줄이는 방법', 'tags' => ['주방위생', '교체주기']],
                    ['title' => '음식물 쓰레기 냄새 줄이는 보관법', 'excerpt' => '밀폐와 배출 타이밍으로 음식물 쓰레기 악취를 줄이는 팁', 'tags' => ['음식물쓰레기', '악취관리']],
                    ['title' => '분리수거 실수 줄이는 품목 정리표', 'excerpt' => '헷갈리는 품목만 따로 정리해 분리배출 정확도를 높이는 방법', 'tags' => ['분리수거', '재활용']],
                    ['title' => '침대 시트 한 번에 깔끔하게 교체하는 순서', 'excerpt' => '베개, 매트리스, 이불 순서를 고정해 교체 시간을 줄이는 팁', 'tags' => ['침구교체', '정돈']],
                    ['title' => '다림질 없이 셔츠 주름 줄이는 건조법', 'excerpt' => '탈수 강도와 걸이 배치로 셔츠 주름을 최소화하는 방법', 'tags' => ['셔츠관리', '주름방지']],
                    ['title' => '욕실 배수구 막힘 예방 루틴', 'excerpt' => '머리카락과 비누찌꺼기를 주기적으로 제거해 배수 문제를 막는 팁', 'tags' => ['배수구관리', '욕실관리']],
                    ['title' => '계절 옷 보관 전 꼭 해야 할 준비', 'excerpt' => '세탁, 건조, 방충 단계를 거쳐 옷 손상을 줄이는 보관 방법', 'tags' => ['옷보관', '계절정리']],
                ],
            ],
            '요리' => [
                'base_tags' => ['요리', '자취요리', '식단'],
                'tips' => [
                    ['title' => '15분 완성 계란볶음밥 기본 공식', 'excerpt' => '재료 비율만 기억하면 실패 없이 빠르게 완성하는 볶음밥 팁', 'tags' => ['볶음밥', '초간단요리']],
                    ['title' => '냉동 채소로 반찬 3가지 돌려먹기', 'excerpt' => '냉동 채소를 활용해 시간과 비용을 아끼는 반찬 루틴', 'tags' => ['냉동채소', '반찬']],
                    ['title' => '파스타 면 삶기 실패 줄이는 시간표', 'excerpt' => '면 종류별 시간 관리로 식감을 안정적으로 맞추는 방법', 'tags' => ['파스타', '면삶기']],
                    ['title' => '국물요리 간 맞추기 쉬운 비율', 'excerpt' => '소금, 간장, 물 비율 기준으로 국물 맛 편차를 줄이는 팁', 'tags' => ['국물요리', '간맞추기']],
                    ['title' => '남은 밥으로 주먹밥 도시락 만들기', 'excerpt' => '남은 밥과 기본 재료로 간단하게 도시락을 준비하는 방법', 'tags' => ['도시락', '남은밥활용']],
                    ['title' => '프라이팬 하나로 단백질+채소 한 끼', 'excerpt' => '세척 부담을 줄이면서 영양 균형을 맞추는 원팬 레시피', 'tags' => ['원팬요리', '단백질식단']],
                    ['title' => '밀프렙 입문용 3일 식단 준비법', 'excerpt' => '한 번 조리로 3일 식사를 준비하는 초보자용 밀프렙 가이드', 'tags' => ['밀프렙', '주간식단']],
                    ['title' => '전자레인지로 감자 포슬하게 익히는 법', 'excerpt' => '물기와 랩 타이밍 조절로 감자를 고르게 익히는 방법', 'tags' => ['전자레인지요리', '감자요리']],
                    ['title' => '자취생 김치 보관과 활용 팁', 'excerpt' => '소분 보관으로 김치 맛과 냄새를 관리하는 실전 방법', 'tags' => ['김치보관', '자취반찬']],
                    ['title' => '조리도구 최소화하는 주말 대량조리', 'excerpt' => '주말 1회 조리로 평일 식사 준비 시간을 줄이는 방법', 'tags' => ['대량조리', '주말준비']],
                ],
            ],
            '건강' => [
                'base_tags' => ['건강', '습관개선', '컨디션관리'],
                'tips' => [
                    ['title' => '앉아 있는 시간 줄이는 1시간 스트레칭', 'excerpt' => '업무 중 1시간마다 짧게 몸을 풀어 통증과 피로를 줄이는 방법', 'tags' => ['스트레칭', '사무실건강']],
                    ['title' => '물 2리터 습관 만드는 시간대별 방법', 'excerpt' => '시간대 기준으로 나눠 마셔 수분 섭취를 꾸준히 유지하는 팁', 'tags' => ['수분섭취', '물마시기']],
                    ['title' => '눈 피로 줄이는 20-20-20 규칙 실천법', 'excerpt' => '디지털 기기 사용 중 눈 피로를 완화하는 기본 루틴', 'tags' => ['눈건강', '디지털피로']],
                    ['title' => '아침 공복 산책이 힘들지 않게 시작하는 법', 'excerpt' => '걷기 강도와 시간부터 낮춰 지속 가능한 습관으로 만드는 팁', 'tags' => ['아침산책', '걷기습관']],
                    ['title' => '야식 줄이는 저녁 루틴', 'excerpt' => '식사 타이밍과 대체 간식으로 야식 충동을 줄이는 방법', 'tags' => ['야식관리', '식습관']],
                    ['title' => '어깨 결림 완화하는 책상 세팅', 'excerpt' => '모니터 높이와 의자 위치 조정으로 어깨 부담을 줄이는 방법', 'tags' => ['자세교정', '목어깨통증']],
                    ['title' => '수면의 질 높이는 침실 온도 관리', 'excerpt' => '적정 온습도 유지로 깊은 잠을 돕는 침실 세팅 팁', 'tags' => ['수면관리', '침실환경']],
                    ['title' => '잔병치레 줄이는 손 씻기 포인트', 'excerpt' => '상황별 손 씻기 타이밍을 지켜 감염 리스크를 낮추는 방법', 'tags' => ['위생', '손씻기']],
                    ['title' => '생리주기 컨디션 기록 활용법', 'excerpt' => '주기별 몸 상태를 기록해 일정과 식단을 조절하는 팁', 'tags' => ['여성건강', '컨디션기록']],
                    ['title' => '주말 폭식 후 회복 식단 가이드', 'excerpt' => '극단적 절식 없이 균형 식단으로 몸 상태를 회복하는 방법', 'tags' => ['식단회복', '폭식관리']],
                ],
            ],
            '재테크' => [
                'base_tags' => ['재테크', '지출관리', '절약'],
                'tips' => [
                    ['title' => '월급날 10분 예산 배분 루틴', 'excerpt' => '고정비, 생활비, 저축을 먼저 나눠 과소비를 줄이는 방법', 'tags' => ['예산관리', '월급관리']],
                    ['title' => '고정비 점검으로 통신비 줄이는 방법', 'excerpt' => '요금제와 부가서비스를 점검해 매달 나가는 비용을 줄이는 팁', 'tags' => ['통신비절약', '고정비']],
                    ['title' => '카드값 폭주 막는 결제일 설계', 'excerpt' => '카드 결제일 분산으로 현금흐름을 안정적으로 관리하는 방법', 'tags' => ['카드관리', '현금흐름']],
                    ['title' => '비상금 통장 분리해서 지키는 법', 'excerpt' => '생활비 계좌와 분리해 비상금 인출을 줄이는 실전 방법', 'tags' => ['비상금', '통장분리']],
                    ['title' => '구독서비스 자동결제 정리 체크리스트', 'excerpt' => '미사용 구독을 정리해 고정 지출을 줄이는 월간 점검법', 'tags' => ['구독관리', '자동결제']],
                    ['title' => '생활비 가계부 3분 기록법', 'excerpt' => '항목 최소화로 가계부 기록을 꾸준히 유지하는 방법', 'tags' => ['가계부', '소비기록']],
                    ['title' => '중고거래로 생활비 아끼는 실전 팁', 'excerpt' => '안 쓰는 물건 판매와 필요한 물품 구매를 병행하는 절약법', 'tags' => ['중고거래', '생활비절약']],
                    ['title' => '여행 적금 목표 세우는 방식', 'excerpt' => '기간과 금액을 쪼개 무리 없이 여행 자금을 모으는 방법', 'tags' => ['여행적금', '목표저축']],
                    ['title' => '연말 지출 몰림 막는 월별 준비법', 'excerpt' => '명절, 선물, 모임비를 월별로 나눠 지출 충격을 줄이는 팁', 'tags' => ['연말지출', '월별예산']],
                    ['title' => '소비 전 24시간 대기 규칙 적용하기', 'excerpt' => '충동 구매를 줄이는 가장 단순한 소비 필터링 방법', 'tags' => ['충동구매', '소비습관']],
                ],
            ],
            '디지털' => [
                'base_tags' => ['디지털', '앱활용', '생산성'],
                'tips' => [
                    ['title' => '스마트폰 알림 줄여 집중력 지키는 설정', 'excerpt' => '필수 알림만 남겨 디지털 피로를 줄이는 스마트폰 설정법', 'tags' => ['알림관리', '집중력']],
                    ['title' => '클라우드 사진 자동정리 폴더 구조', 'excerpt' => '촬영 시점 기준 폴더 규칙으로 사진 찾는 시간을 줄이는 방법', 'tags' => ['클라우드', '사진정리']],
                    ['title' => '비밀번호 관리앱 처음 세팅하는 법', 'excerpt' => '중복 비밀번호를 줄이고 보안을 높이는 기본 설정 가이드', 'tags' => ['보안', '비밀번호관리']],
                    ['title' => '무료 캘린더로 가족 일정 공유하기', 'excerpt' => '공유 캘린더로 일정 충돌을 줄이는 운영 방법', 'tags' => ['일정공유', '캘린더앱']],
                    ['title' => '이메일 받은편지함 0에 가깝게 유지하는 루틴', 'excerpt' => '분류 규칙과 처리 시간을 고정해 메일 누적을 줄이는 방법', 'tags' => ['이메일정리', '업무효율']],
                    ['title' => '브라우저 북마크 대신 읽기목록 쓰는 법', 'excerpt' => '저장 정보 과부하를 줄여 필요한 자료만 다시 보는 방법', 'tags' => ['브라우저', '정보관리']],
                    ['title' => '온라인 회의 음질 좋아지는 기본 세팅', 'excerpt' => '마이크 위치와 노이즈 설정으로 전달력을 높이는 팁', 'tags' => ['화상회의', '음질개선']],
                    ['title' => '중고폰 판매 전 개인정보 완전 삭제', 'excerpt' => '백업과 초기화 절차를 지켜 개인정보 유출을 막는 방법', 'tags' => ['중고폰', '개인정보보호']],
                    ['title' => 'AI 요약도구로 문서 읽기 시간 줄이기', 'excerpt' => '핵심만 빠르게 확인하고 원문 검토 시간을 줄이는 활용법', 'tags' => ['AI도구', '문서요약']],
                    ['title' => '공공 와이파이 사용할 때 보안 체크', 'excerpt' => '공용 네트워크에서 계정 보호를 위한 기본 안전 수칙', 'tags' => ['와이파이보안', '계정보호']],
                ],
            ],
            '자취' => [
                'base_tags' => ['자취', '원룸생활', '생활비'],
                'tips' => [
                    ['title' => '원룸 수납 공간 늘리는 수직정리 팁', 'excerpt' => '벽면과 선반을 활용해 바닥 면적을 확보하는 수납 전략', 'tags' => ['원룸수납', '공간정리']],
                    ['title' => '자취방 전기요금 줄이는 습관', 'excerpt' => '가전 사용 패턴을 조정해 월 전기요금을 낮추는 실전 방법', 'tags' => ['전기요금', '에너지절약']],
                    ['title' => '소량 장보기로 음식물 쓰레기 줄이기', 'excerpt' => '주 2회 소량 구매로 식재료 폐기를 줄이는 장보기 팁', 'tags' => ['식재료관리', '소량구매']],
                    ['title' => '혼밥 질리지 않게 반찬 로테이션', 'excerpt' => '베이스 반찬 3개를 돌려 먹어 혼밥 피로를 줄이는 방법', 'tags' => ['혼밥', '반찬로테이션']],
                    ['title' => '택배 수령 놓치지 않는 문앞 관리법', 'excerpt' => '택배 예정일과 보관 장소를 정해 수령 누락을 줄이는 팁', 'tags' => ['택배수령', '문앞정리']],
                    ['title' => '자취방 곰팡이 예방 환기 루틴', 'excerpt' => '습도 높은 구간을 중심으로 환기 시간을 고정하는 방법', 'tags' => ['곰팡이예방', '습도관리']],
                    ['title' => '월세·관리비 납부 자동화 방법', 'excerpt' => '자동이체와 알림을 함께 사용해 납부 누락을 방지하는 방법', 'tags' => ['월세관리', '자동이체']],
                    ['title' => '갑작스런 손님 대비 15분 정리법', 'excerpt' => '보이는 구역 우선 정리로 빠르게 공간을 정돈하는 팁', 'tags' => ['손님맞이', '빠른정리']],
                    ['title' => '기본 상비약 키트 구성 리스트', 'excerpt' => '자취방에서 자주 필요한 상비약을 항목별로 준비하는 방법', 'tags' => ['상비약', '응급준비']],
                    ['title' => '이사 전 체크리스트로 누락 막기', 'excerpt' => '계약, 포장, 주소 변경을 단계별로 관리하는 이사 준비법', 'tags' => ['이사준비', '체크리스트']],
                ],
            ],
            '여행' => [
                'base_tags' => ['여행', '여행준비', '체크리스트'],
                'tips' => [
                    ['title' => '2박3일 캐리어 짐싸기 기본 공식', 'excerpt' => '의류, 세면도구, 전자기기 순서로 빠르게 짐을 꾸리는 방법', 'tags' => ['캐리어정리', '짐싸기']],
                    ['title' => '항공권 가격 비교할 때 확인할 항목', 'excerpt' => '수하물, 환불 조건, 시간대를 함께 비교해 총비용을 줄이는 팁', 'tags' => ['항공권', '가격비교']],
                    ['title' => '숙소 체크인 전 후기에서 볼 핵심', 'excerpt' => '청결, 소음, 교통 접근성을 중심으로 후기 읽는 기준', 'tags' => ['숙소선택', '여행후기']],
                    ['title' => '여행 경비 기록을 간단히 남기는 방법', 'excerpt' => '카테고리 3개만 기록해 지출 파악을 쉽게 하는 방법', 'tags' => ['여행경비', '기록습관']],
                    ['title' => '비행기 지연 대비 공항 대기 루틴', 'excerpt' => '충전, 식사, 탑승구 동선으로 대기 스트레스를 줄이는 팁', 'tags' => ['공항대기', '비행지연']],
                    ['title' => '해외여행 데이터 로밍 아끼는 설정', 'excerpt' => '데이터 사용량을 통제해 로밍 요금 부담을 줄이는 방법', 'tags' => ['해외로밍', '데이터절약']],
                    ['title' => '여행지에서 분실물 줄이는 소지품 관리', 'excerpt' => '소지품 분산 보관으로 분실 리스크를 줄이는 실전 팁', 'tags' => ['분실예방', '소지품관리']],
                    ['title' => '아이와 함께 여행할 때 동선 짜는 법', 'excerpt' => '이동 거리와 휴식 시간을 고려해 가족 여행 피로를 줄이는 방법', 'tags' => ['가족여행', '여행동선']],
                    ['title' => '우천 여행 대체 일정 미리 준비하기', 'excerpt' => '비 오는 날에도 일정이 무너지지 않도록 플랜B를 준비하는 팁', 'tags' => ['우천여행', '플랜B']],
                    ['title' => '귀국 후 피로 줄이는 하루 정리 루틴', 'excerpt' => '세탁, 정리, 수면 리셋으로 여행 후유증을 줄이는 방법', 'tags' => ['귀국루틴', '피로회복']],
                ],
            ],
        ];
    }
}
