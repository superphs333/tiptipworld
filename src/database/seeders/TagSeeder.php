<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->defaultTags() as $attributes) {
                $tag = Tag::query()->firstWhere('name', $attributes['name']);

                if ($tag === null) {
                    Tag::query()->create($attributes);

                    continue;
                }

                $tag->fill([
                    'is_blocked' => $attributes['is_blocked'],
                ]);

                $tag->save();
            }
        });
    }

    private function defaultTags(): array
    {
        return [
            ['name' => '정리', 'is_blocked' => false],
            ['name' => '청소', 'is_blocked' => false],
            ['name' => '세탁', 'is_blocked' => false],
            ['name' => '보관', 'is_blocked' => false],
            ['name' => '절약', 'is_blocked' => false],
            ['name' => '루틴', 'is_blocked' => false],
            ['name' => '건강관리', 'is_blocked' => false],
            ['name' => '시간관리', 'is_blocked' => false],
            ['name' => '요리기초', 'is_blocked' => false],
            ['name' => '자취생', 'is_blocked' => false],
            ['name' => '스마트폰', 'is_blocked' => false],
            ['name' => '앱추천', 'is_blocked' => false],
            ['name' => '출근팁', 'is_blocked' => false],
            ['name' => '여행준비', 'is_blocked' => false],
            ['name' => '비상용', 'is_blocked' => false],
        ];
    }
}
