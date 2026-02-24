

<section class="home-category" id="all-categories" aria-label="모든 카테고리">
    <header class="home-popular__head home-category__head">
        <div>
            <p class="home-popular__eyebrow">CATEGORIES</p>
            <h2 class="home-popular__title home-category__title">카테고리</h2>
        </div>
    </header>

    <div class="home-category__grid" role="list">
        @foreach ($categories as $category)
            <a href="{{ route('tips.category', ['category_id' => $category['id']]) }}" class="home-category__link" target="_blank" rel="noopener noreferrer">

                <article class="home-category__card" role="listitem" aria-label="{{ $category['name'] }}">
                    <p class="home-category__name">{{ $category['name'] }}</p>
                    <p class="home-category__desc">{{ $category['description'] }}</p>
                    <p class="home-category__count">{{ number_format($category['tips_count']) }}개 팁</p>
                </article>
            </a>
        @endforeach
    </div>
</section>
