<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberNicknameSeeder extends Seeder
{
    public function run(): void
    {
        $nicknamePool = $this->nicknamePool();
        $poolSize = count($nicknamePool);

        if ($poolSize === 0) {
            return;
        }

        DB::transaction(function () use ($nicknamePool, $poolSize): void {
            $targets = User::query()
                ->where(function ($query): void {
                    $query->where('name', 'like', '멤버%')
                        ->orWhere('email', 'like', 'member%@example.com');
                })
                ->orderBy('id')
                ->get();

            foreach ($targets as $index => $user) {
                $baseName = $nicknamePool[$index % $poolSize];
                $round = intdiv($index, $poolSize);
                $resolvedName = $round === 0 ? $baseName : sprintf('%s%d', $baseName, $round + 1);

                $user->forceFill([
                    'name' => $resolvedName,
                ])->save();
            }
        });
    }

    private function nicknamePool(): array
    {
        return [
            '정리하는수연',
            '아침형민우',
            '루틴장인유나',
            '주방탐험가지후',
            '퇴근후정리러',
            '소소한재테크맘',
            '살림초보은지',
            '운동기록태현',
            '집밥메이커다은',
            '주말청소대장',
            '피부관리연구원',
            '디지털미니멀진',
            '정돈취미재원',
            '여행준비박사',
            '1인가구현아',
            '집중루틴세영',
            '식단챌린저하람',
            '수납고수예준',
            '절약하는연우',
            '오늘도클린업',
            '미니멀라이프준',
            '저녁요리러버',
            '습관트래커지안',
            '살림연습생도윤',
            '계획세우는나래',
            '생활꿀팁수집가',
            '정리습관메이커',
            '아껴쓰는서윤',
            '루틴러버건우',
            '꾸준함한스푼',
        ];
    }
}
