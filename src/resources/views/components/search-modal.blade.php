@props([
    'title' => '검색',
    'date' => now()->format('Y.m.d'),
    'rankings' => [],
    'action' => route('tips.search'),
    'queryName' => 'query',
])

@php
    $rows = collect($rankings)
        ->filter(static fn ($row) => filled(data_get($row, 'keyword', data_get($row, 'label'))))
        ->take(5)
        ->values();
@endphp

<x-modal name="global-search" maxWidth="4xl" focusable>
    <section
        x-data
        x-on:open-modal.window="if ($event.detail === 'global-search') { $refs.searchForm?.reset(); }"
        class="space-y-5 p-6 sm:p-8"
        aria-label="검색 모달"
    >
        <header class="flex items-start justify-between pb-1">
            <div class="space-y-1">
                <h2 class="text-xl font-semibold text-gray-900">{{ $title }}</h2>
                <p class="text-xs font-medium tracking-wide text-gray-500">{{ $date }}</p>
            </div>

            <button
                x-data
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 text-xl leading-none text-gray-700 hover:bg-gray-50"
                aria-label="닫기"
                @click="$dispatch('close-modal', 'global-search')"
            >
                &times;
            </button>
        </header>

        <form method="GET" action="{{ $action }}" class="flex items-center gap-2" x-ref="searchForm">
            <input
                type="search"
                name="{{ $queryName }}"
                placeholder="검색어를 입력하세요"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                class="h-12 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-900 focus:border-gray-500 focus:outline-none"
            />

            <button
                type="submit"
                class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                aria-label="검색 실행"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                </svg>
            </button>
        </form>

        @if ($rows->isNotEmpty())
            <section class="space-y-2" aria-label="검색 키워드 결과">
                <div class="px-1 text-sm font-semibold text-gray-800">키워드 결과</div>

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="flex items-center gap-4 bg-gray-50 px-4 py-2 text-xs font-semibold tracking-wide text-gray-600">
                        <span class="w-[70px] shrink-0">순위</span>
                        <span class="min-w-0 flex-1">키워드</span>
                    </div>

                    <ul class="divide-y divide-gray-200">
                        @foreach ($rows as $index => $row)
                            @php
                                $rank = data_get($row, 'rank', $index + 1);
                                $keyword = data_get($row, 'keyword', data_get($row, 'label', '-'));
                                $keywordSearchUrl = route('tips.search', [$queryName => $keyword]);
                            @endphp
                            <li class="flex items-center gap-4 px-4 py-3 text-sm text-gray-800">
                                <span class="w-[70px] shrink-0 font-semibold">{{ $rank }}</span>
                                <a
                                    href="{{ $keywordSearchUrl }}"
                                    class="min-w-0 flex-1 truncate text-gray-800 hover:text-gray-900 hover:underline"
                                    aria-label="{{ $keyword }} 검색 결과로 이동"
                                >
                                    {{ $keyword }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </section>
</x-modal>
