<section class="home-popular" data-home-popular aria-label="최근 인기글">
    <header class="home-popular__head">
        <div>
            <p class="home-popular__eyebrow">RECENT</p>
            <h2 class="home-popular__title">최근 인기글</h2>
        </div>
        <div class="home-popular__nav">
            <button type="button" class="home-popular__nav-btn" data-home-popular-nav="prev" aria-label="이전 카드">
                <span aria-hidden="true">←</span>
            </button>
            <button type="button" class="home-popular__nav-btn" data-home-popular-nav="next" aria-label="다음 카드">
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </header>

    <div class="home-popular__track" data-home-popular-track>
        @forelse ($tips as $item)
            <x-tip.card :item="$item" :open-in-new-tab="true" />
        @empty
            <p class="home-popular__empty">인기글이 없습니다.</p>
        @endforelse
    </div>
</section>

@once
    <script>
        (() => {
            const roots = document.querySelectorAll('[data-home-popular]');

            roots.forEach((root) => {
                const track = root.querySelector('[data-home-popular-track]');
                if (!track) {
                    return;
                }

                const prevBtn = root.querySelector('[data-home-popular-nav="prev"]');
                const nextBtn = root.querySelector('[data-home-popular-nav="next"]');
                const step = () => {
                    const card = track.querySelector('.home-popular__card');
                    const computed = getComputedStyle(track);
                    const gap = parseInt(computed.columnGap || computed.gap || '12', 10);

                    if (card) {
                        return Math.round(card.getBoundingClientRect().width + (Number.isNaN(gap) ? 12 : gap));
                    }

                    return Math.max(220, Math.floor(track.clientWidth * 0.8));
                };

                prevBtn?.addEventListener('click', () => {
                    track.scrollBy({ left: -step(), behavior: 'smooth' });
                });

                nextBtn?.addEventListener('click', () => {
                    track.scrollBy({ left: step(), behavior: 'smooth' });
                });
            });
        })();
    </script>
@endonce
