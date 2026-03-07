<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->defaultUsers() as $index => $attributes) {
                $user = User::query()->firstOrCreate(
                    ['email' => $attributes['email']],
                    [
                        'name' => $attributes['name'],
                        'password' => Hash::make('password'),
                        'status' => 'active',
                    ]
                );

                $user->forceFill([
                    'name' => $attributes['name'],
                    'status' => 'active',
                    'email_verified_at' => $user->email_verified_at ?? now()->subDays($index + 1),
                ])->save();
            }
        });
    }

    private function defaultUsers(): array
    {
        $authors = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => '민지', 'email' => 'minji@example.com'],
            ['name' => '지훈', 'email' => 'jihoon@example.com'],
            ['name' => '서연', 'email' => 'seoyeon@example.com'],
            ['name' => '도윤', 'email' => 'doyoon@example.com'],
            ['name' => '하은', 'email' => 'haeun@example.com'],
            ['name' => '유진', 'email' => 'yujin@example.com'],
            ['name' => '현우', 'email' => 'hyunwoo@example.com'],
            ['name' => '수아', 'email' => 'sua@example.com'],
            ['name' => '준서', 'email' => 'junseo@example.com'],
        ];

        $memberNames = [
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

        $members = [];
        foreach ($memberNames as $index => $memberName) {
            $memberNumber = $index + 1;
            $members[] = [
                'name' => $memberName,
                'email' => sprintf('member%02d@example.com', $memberNumber),
            ];
        }

        return array_merge($authors, $members);
    }
}
