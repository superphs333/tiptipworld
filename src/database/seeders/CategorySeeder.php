<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $nextSortOrder = (int) (Category::query()->max('sort_order') ?? 0);

            foreach ($this->defaultCategories() as $attributes) {
                $category = Category::query()->firstWhere('name', $attributes['name']);

                if ($category === null) {
                    Category::query()->create($attributes + [
                        'sort_order' => ++$nextSortOrder,
                    ]);

                    continue;
                }

                $category->fill([
                    'description' => $attributes['description'],
                    'is_active' => $attributes['is_active'],
                ]);

                if ($category->sort_order === null) {
                    $category->sort_order = ++$nextSortOrder;
                }

                $category->save();
            }
        });
    }

    private function defaultCategories(): array
    {
        return [
            [
                'name' => '생활',
                'description' => '일상에서 바로 적용할 수 있는 기본 생활 팁',
                'is_active' => true,
            ],
            [
                'name' => '집안일',
                'description' => '청소, 세탁, 정리처럼 집에서 자주 쓰는 팁',
                'is_active' => true,
            ],
            [
                'name' => '요리',
                'description' => '간단한 조리법과 주방에서 유용한 팁',
                'is_active' => true,
            ],
            [
                'name' => '건강',
                'description' => '컨디션 관리와 습관 개선에 도움이 되는 팁',
                'is_active' => true,
            ],
            [
                'name' => '재테크',
                'description' => '지출 관리와 돈 관리에 도움 되는 팁',
                'is_active' => true,
            ],
            [
                'name' => '디지털',
                'description' => '앱, 기기, 온라인 서비스 활용 팁',
                'is_active' => true,
            ],
            [
                'name' => '자취',
                'description' => '혼자 살 때 자주 필요한 실전 팁',
                'is_active' => true,
            ],
            [
                'name' => '여행',
                'description' => '이동, 짐 정리, 예약에 유용한 팁',
                'is_active' => true,
            ],
        ];
    }
}
