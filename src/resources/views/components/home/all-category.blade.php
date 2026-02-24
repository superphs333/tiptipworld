<section class="home-category" id="all-categories" aria-label="모든 카테고리">
    <header class="home-popular__head home-category__head">
        <div>
            <p class="home-popular__eyebrow">CATEGORIES</p>
            <h2 class="home-popular__title home-category__title">카테고리</h2>
        </div>
    </header>

    <div class="home-category__grid" role="list">
        @foreach ($categories as $category)
            @php
                $categoryName = (string) data_get($category, 'name', '카테고리');
                $categoryDesc = (string) data_get($category, 'description', '카테고리 설명이 준비 중입니다.');
                $categoryCount = (int) data_get($category, 'tips_count', 0);
                $categoryId = (int) data_get($category, 'id', 0);
                $glyph = mb_substr($categoryName, 0, 1);
                $tone = $loop->index % 6;
            @endphp

            <a
                href="{{ route('tips.category', ['category_id' => $categoryId]) }}"
                class="home-category__link"
                target="_blank"
                rel="noopener noreferrer"
            >
                <article
                    class="home-category__card"
                    data-tone="{{ $tone }}"
                    role="listitem"
                    aria-label="{{ $categoryName }}"
                >
                    <div class="home-category__top">
                        <p class="home-category__rank">#{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <span class="home-category__glyph" aria-hidden="true">{{ $glyph }}</span>
                    </div>

                    <p class="home-category__name">{{ $categoryName }}</p>
                    <p class="home-category__desc">{{ $categoryDesc }}</p>

                    <div class="home-category__bottom">
                        <p class="home-category__count">{{ number_format($categoryCount) }}개 팁</p>
                        <p class="home-category__cta">둘러보기 <span aria-hidden="true">→</span></p>
                    </div>
                </article>
            </a>
        @endforeach
    </div>
</section>
