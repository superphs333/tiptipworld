<section class="tip-wireframe" data-tip-wireframe data-tip-id="{{ $tip->id }}">
    <div class="tip-wireframe__topbar">
        <a class="tip-wireframe__back-link" href="{{ route('home') }}">← 목록</a>
        <div class="tip-wireframe__topbar-right">
            <button class="tip-wireframe__icon-btn tip-wireframe__mobile-only" type="button" aria-label="공유">공유</button>
            <button class="tip-wireframe__icon-btn" type="button" aria-label="더보기">⋯</button>
        </div>
    </div>

    <div class="tip-wireframe__layout">
        <aside class="tip-wireframe__toc" data-toc-desktop hidden>
            <div class="tip-wireframe__toc-header">
                <strong>TOC</strong>
                <button type="button" class="tip-wireframe__toc-toggle" data-toc-toggle="desktop" aria-expanded="false">펼치기</button>
            </div>
            <nav class="tip-wireframe__toc-panel is-collapsed" data-toc-panel="desktop" aria-label="목차">
                <ol class="tip-wireframe__toc-list" data-toc-list="desktop"></ol>
            </nav>
        </aside>

        <article class="tip-wireframe__article">
            <header class="tip-wireframe__article-header">
                <div class="tip-wireframe__header-top">
                    <span class="tip-wireframe__category-chip">{{ $tip->categoryName }}</span>
                    <div class="tip-wireframe__badge-row">
                        @if (filled($tip->status))
                            <span class="tip-wireframe__badge tip-wireframe__badge--soft">{{ strtoupper((string) $tip->status) }}</span>
                        @endif
                        @if (filled($tip->visibility))
                            <span class="tip-wireframe__badge tip-wireframe__badge--soft">{{ strtoupper((string) $tip->visibility) }}</span>
                        @endif
                    </div>
                </div>
                <h1 class="tip-wireframe__title">{{ $tip->title }}</h1>
                <p class="tip-wireframe__meta">
                    {{ $tip->authorName }} · {{ $tip->createdDate }} · 조회 {{ number_format((int) ($tip->view_count ?? 0)) }}
                </p>
            </header>

            {{-- 썸네일 --}}
            @if(!blank($tip->thumbnail))
                <figure class="tip-wireframe__thumbnail">
                    <img src="{{ $tip->thumbnailUrl }}" alt="{{ $tip->title }}" loading="lazy">
                </figure>
            @endif


            <section class="tip-wireframe__toc-mobile" data-toc-mobile hidden>
                <button type="button" class="tip-wireframe__toc-toggle tip-wireframe__toc-toggle--mobile" data-toc-toggle="mobile" aria-expanded="false">
                    목차 ▾
                </button>
                <nav class="tip-wireframe__toc-panel is-collapsed" data-toc-panel="mobile" aria-label="모바일 목차">
                    <ol class="tip-wireframe__toc-list" data-toc-list="mobile"></ol>
                </nav>
            </section>

            {{-- <p class="tip-wireframe__excerpt">
                {{ $excerpt !== '' ? $excerpt : '요약(Excerpt) 영역입니다. 핵심 메시지를 2~3줄로 먼저 전달합니다.' }}
            </p> --}}

            <hr class="tip-wireframe__divider">

            <section class="tip-wireframe__content" data-tip-article>
                @if ($tip->content !== '')
                    {!! $tip->content !!}
                @else
                    <h2>1. 문제 정의</h2>
                    <p>이 구간은 본문(Content) 와이어프레임입니다. 실제 게시글이 없을 때 구조 확인용 더미 텍스트를 보여줍니다.</p>
                    <h3>1-1. 독자 목표</h3>
                    <p>읽는 사람이 무엇을 얻어가야 하는지 짧고 명확하게 정의합니다. 한 문단은 3~5줄 이내를 권장합니다.</p>
                    <h2>2. 실행 단계</h2>
                    <p>단계별로 분리해 설명하면 가독성이 올라갑니다. H2/H3 위계는 TOC 자동 생성 기준으로 사용됩니다.</p>
                    <h3>2-1. 체크포인트</h3>
                    <p>중요한 항목은 체크리스트나 표로 정리하고, 부가 설명은 뒤쪽에 배치합니다.</p>
                    <h2>3. 마무리</h2>
                    <p>핵심 요약, 관련 링크, 다음 액션을 정리합니다. 댓글 섹션에서 추가 질문을 받을 수 있습니다.</p>
                @endif
            </section>

            <hr class="tip-wireframe__divider">

            <section class="tip-wireframe__section">
                <h2 class="tip-wireframe__section-title">태그</h2>
                
                    @if(!blank($tip->displayTags))
                        <div class="tip-wireframe__tags">
                            @foreach($tip->displayTags as $tag)
                                <span class="tip-wireframe__tag">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

            </section>

            {{-- <section class="tip-wireframe__section">
                <h2 class="tip-wireframe__section-title">관련 팁</h2>
                <div class="tip-wireframe__related-grid">
                    <article class="tip-wireframe__related-item">
                        <h3>관련 팁 01</h3>
                        <p>관련 콘텐츠가 카드 또는 리스트로 노출됩니다.</p>
                    </article>
                    <article class="tip-wireframe__related-item">
                        <h3>관련 팁 02</h3>
                        <p>3~5개 범위 내에서 추천 항목을 배치합니다.</p>
                    </article>
                    <article class="tip-wireframe__related-item">
                        <h3>관련 팁 03</h3>
                        <p>요약 한 줄과 이동 액션만 남겨 정보 밀도를 조절합니다.</p>
                    </article>
                </div>
            </section> --}}

            <section class="tip-wireframe__section tip-wireframe__comments" id="tip-comments">
                <h2 class="tip-wireframe__section-title">댓글</h2>
                <form class="tip-wireframe__comment-form" action="#" method="post" onsubmit="return false;">
                    <label for="tip-wireframe-comment">댓글 입력</label>
                    <textarea id="tip-wireframe-comment" placeholder="댓글 입력 + 리스트 구조를 확인하기 위한 와이어프레임 영역"></textarea>
                    <button type="submit">댓글 등록</button>
                </form>
                <ul class="tip-wireframe__comment-list">
                    <li>
                        <strong>독자 A</strong>
                        <p>정리된 구조 덕분에 읽기 편해졌어요.</p>
                    </li>
                    <li>
                        <strong>독자 B</strong>
                        <p>모바일 하단 액션바가 있어서 바로 반응하기 좋습니다.</p>
                    </li>
                </ul>
            </section>
        </article>

        @php
            $isLiked = auth()->check() ? $tip->isLikedBy(auth()->user()) : false;
        @endphp
        <aside class="tip-wireframe__action">
            <div class="tip-wireframe__action-sticky">
                <button type="button" class="tip-wireframe__action-btn" aria-label="저장">
                    <span class="tip-wireframe__action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-wireframe__action-label">저장</span>
                </button>                
                <button
                    type="button"
                    class="tip-wireframe__action-btn {{ $isLiked ? 'is-liked' : '' }}"
                    aria-label="좋아요"
                    data-tip-action="like"
                    aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
                    data-tip-id="{{ $tip->id }}"
                >
                    <span class="tip-wireframe__action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-wireframe__action-label">좋아요</span>
                    <span class="tip-wireframe__action-count" data-like-count>{{ number_format((int) ($tip->like_count ?? 0)) }}</span>
                </button>
                <button type="button" class="share_btn tip-wireframe__action-btn" aria-label="공유"
                data-tip-action="share"
                data-title="{{ $tip_data_for_share['url_tip_title'] }}"
                data-text = "{{ $tip_data_for_share['url_tip_text'] }}"
                data-url = "{{ $tip_data_for_share['url_tip_url'] }}"
                >
                    <span class="tip-wireframe__action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M9.5 13.5 14.5 8.5M8.16 9.84l-2.12 2.12a3 3 0 1 0 4.24 4.24l2.12-2.12M15.84 14.16l2.12-2.12a3 3 0 1 0-4.24-4.24l-2.12 2.12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-wireframe__action-label">공유</span>
                </button>
                <a class="tip-wireframe__action-btn" href="#tip-comments" aria-label="댓글">
                    <span class="tip-wireframe__action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M5.25 7.5a3.75 3.75 0 0 1 3.75-3.75h6a3.75 3.75 0 0 1 3.75 3.75v5A3.75 3.75 0 0 1 15 16.25H10.6l-3.35 3.14a.5.5 0 0 1-.84-.36v-2.78A3.75 3.75 0 0 1 5.25 12.5v-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-wireframe__action-label">댓글</span>
                </a>

                {{-- <section class="tip-wireframe__author-card">
                    <h3>작성자 카드</h3>
                    <p>{{ $tip->authorName }}</p>
                    <small>간단 소개 영역</small>
                </section>

                <section class="tip-wireframe__mini-related">
                    <h3>관련 팁</h3>
                    <ul>
                        <li><a href="javascript:void(0)">연결 팁 A</a></li>
                        <li><a href="javascript:void(0)">연결 팁 B</a></li>
                        <li><a href="javascript:void(0)">연결 팁 C</a></li>
                    </ul>
                </section> --}}
            </div>
        </aside>
    </div>
</section>

<script>
(() => {
    const root = document.querySelector('[data-tip-wireframe]');
    if (!root) {
        return;
    }

    const article = root.querySelector('[data-tip-article]');
    const layout = root.querySelector('.tip-wireframe__layout');
    const desktopToc = root.querySelector('[data-toc-desktop]');
    const mobileToc = root.querySelector('[data-toc-mobile]');
    const desktopList = root.querySelector('[data-toc-list="desktop"]');
    const mobileList = root.querySelector('[data-toc-list="mobile"]');

    if (!article || !layout || !desktopToc || !mobileToc || !desktopList || !mobileList) {
        return;
    }

    const headings = Array.from(article.querySelectorAll('h2, h3')).filter((node) => node.textContent.trim().length > 0);
    const textLength = article.textContent.replace(/\s+/g, '').length;
    const isLongPost = textLength >= 550 || headings.length >= 3;

    if (!isLongPost || headings.length === 0) {
        return;
    }

    desktopToc.hidden = false;
    mobileToc.hidden = false;
    layout.classList.add('has-toc');

    headings.forEach((heading, index) => {
        if (!heading.id) {
            heading.id = `tip-heading-${index + 1}`;
        }

        const level = heading.tagName.toLowerCase();
        const text = heading.textContent.trim();

        ['desktop', 'mobile'].forEach((target) => {
            const list = target === 'desktop' ? desktopList : mobileList;
            const item = document.createElement('li');
            item.className = `tip-wireframe__toc-item ${level === 'h3' ? 'is-sub' : ''}`;

            const link = document.createElement('a');
            link.href = `#${heading.id}`;
            link.textContent = text;

            item.appendChild(link);
            list.appendChild(item);
        });
    });

    const setupToggle = (target, collapsedLabel, expandedLabel) => {
        const button = root.querySelector(`[data-toc-toggle="${target}"]`);
        const panel = root.querySelector(`[data-toc-panel="${target}"]`);
        if (!button || !panel) {
            return;
        }

        button.textContent = collapsedLabel;
        button.setAttribute('aria-expanded', 'false');
        panel.classList.add('is-collapsed');

        button.addEventListener('click', () => {
            const isCollapsed = panel.classList.toggle('is-collapsed');
            button.setAttribute('aria-expanded', String(!isCollapsed));
            button.textContent = isCollapsed ? collapsedLabel : expandedLabel;
        });
    };

    setupToggle('desktop', '펼치기', '접기');
    setupToggle('mobile', '목차 ▾', '목차 ▴');
})();
</script>
