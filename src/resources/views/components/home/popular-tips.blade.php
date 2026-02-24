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
            @php
                $authorName = data_get($item, 'user.name', '작성자 미상');
                $authorImage = data_get($item, 'user.profile_image_url', asset('images/avatar-default.svg'));
                $categoryName = data_get($item, 'category.name', '미분류');
                $viewCount = (int) data_get($item, 'view_count', 0);
                $likeCount = (int) data_get($item, 'like_count', 0);
                $commentCount = (int) data_get($item, 'comment_count', 0);
                $bookmarkCount = (int) data_get($item, 'bookmark_count', 0);
            @endphp

            <article class="home-popular__card">
                <a class="home-popular__thumb" href="{{ route('tip.show', ['tip_id' => $item->id]) }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $item->thumbnailUrl }}" alt="{{ $item->title }}" loading="lazy">
                </a>

                <div class="home-popular__body">
                    <span class="home-popular__category">{{ $categoryName }}</span>
                    <a class="home-popular__card-title" href="{{ route('tip.show', ['tip_id' => $item->id]) }}" target="_blank" rel="noopener noreferrer">
                        {{ $item->title }}
                    </a>

                    <div class="home-popular__author-row">
                        <span class="home-popular__author">
                            <img
                                class="home-popular__avatar"
                                src="{{ $authorImage }}"
                                alt="{{ $authorName }} 프로필"
                                loading="lazy"
                            >
                            <span>{{ $authorName }}</span>
                        </span>
                        <span class="home-popular__views" title="조회수">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6" />
                                <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            {{ number_format($viewCount) }}
                        </span>
                    </div>

                    <div class="home-popular__bottom">
                        <span class="home-popular__stats">
                            <span class="home-popular__stat" title="좋아요">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                {{ number_format($likeCount) }}
                            </span>
                            <span class="home-popular__stat" title="댓글">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4.75 6.5a2.25 2.25 0 0 1 2.25-2.25h10a2.25 2.25 0 0 1 2.25 2.25v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7A2.25 2.25 0 0 1 4.75 13.75V6.5Z" stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                {{ number_format($commentCount) }}
                            </span>
                            <span class="home-popular__stat" title="북마크">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                {{ number_format($bookmarkCount) }}
                            </span>
                        </span>
                    </div>
                </div>
            </article>
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
