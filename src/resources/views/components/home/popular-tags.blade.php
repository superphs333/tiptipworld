<section class="home-tags" id="popular-tags" aria-label="인기 태그">
    <header class="home-popular__head home-tags__head">
        <div>
            <p class="home-popular__eyebrow home-tags__eyebrow">TRENDING</p>
            <h2 class="home-popular__title home-tags__title">
                인기 태그                
            </h2>
        </div>
    </header>

    <div class="home-tags__list" role="list">
        @forelse ($tags as $tag)
            @php
                $tagId = (int) data_get($tag, 'id', 0);
                $tagName = ltrim((string) data_get($tag, 'name', '태그'), '#');
                $tagCount = (int) data_get($tag, 'tips_count', data_get($tag, 'usage_count', data_get($tag, 'count', 0)));
                $tagUrl = $tagId > 0 ? route('tips.tag', ['tag_id' => $tagId]) : '#';
            @endphp

            <a
                class="home-tags__chip"
                role="listitem"
                href="{{ $tagUrl }}"
                title="#{{ $tagName !== '' ? $tagName : '태그' }}"
                @if ($tagId > 0) target="_blank" rel="noopener noreferrer" @endif
                @if ($tagId === 0) aria-disabled="true" @endif
            >
                <span class="home-tags__chip-name">#{{ $tagName !== '' ? $tagName : '태그' }}</span>
                <span class="home-tags__chip-count">{{ number_format(max($tagCount, 0)) }}</span>
            </a>
        @empty
            <p class="home-tags__empty">아직 집계된 인기 태그가 없습니다.</p>
        @endforelse
    </div>
</section>
