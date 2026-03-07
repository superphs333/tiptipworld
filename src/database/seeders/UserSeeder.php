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

        $members = [];
        for ($i = 1; $i <= 30; $i++) {
            $members[] = [
                'name' => sprintf('멤버%02d', $i),
                'email' => sprintf('member%02d@example.com', $i),
            ];
        }

        return array_merge($authors, $members);
    }
}
