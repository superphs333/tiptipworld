@props([
    'title' => '검색',
    'summaryLabel' => '합계',
    'summaryValue' => null,
    'date' => now()->format('Y.m.d'),
    'rankings' => [],
    'action' => route('tips.list'),
    'queryName' => 'query',
])

@php
    $rows = collect($rankings)->take(5)->values();
    $query = trim((string) request($queryName, ''));
    $hasQuery = $query !== '';

    if ($rows->isEmpty()) {
        $rows = collect(range(1, 5))->map(static fn ($rank) => [
            'rank' => $rank,
            'keyword' => '-',
            'count' => '-',
        ]);
    }

    $totalCount = $summaryValue;

    if ($totalCount === null) {
        $totalCount = collect($rankings)->sum(static fn ($row) => (int) data_get($row, 'count', 0));
    }
@endphp

<x-modal name="global-search" maxWidth="4xl" focusable>
    <section class="space-y-5 p-6 sm:p-8" aria-label="검색 모달">
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

        <form method="GET" action="{{ $action }}" class="flex items-center gap-2">
            <input
                type="search"
                name="{{ $queryName }}"
                value="{{ request($queryName) }}"
                placeholder="검색어를 입력하세요"
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

        <section class="space-y-2" aria-label="검색 키워드 결과">
            <div class="px-1 text-sm font-semibold text-gray-800">키워드 결과</div>

            <div class="overflow-hidden rounded-lg border border-gray-200">
                <div class="grid grid-cols-[70px_1fr_90px] bg-gray-50 px-4 py-2 text-xs font-semibold tracking-wide text-gray-600">
                    <span>순위</span>
                    <span>키워드</span>
                    <span class="text-right">건수</span>
                </div>

                <ul class="divide-y divide-gray-200">
                    @foreach ($rows as $index => $row)
                        @php
                            $rank = data_get($row, 'rank', $index + 1);
                            $keyword = data_get($row, 'keyword', data_get($row, 'label', '-'));
                            $count = data_get($row, 'count', data_get($row, 'value', '-'));
                        @endphp
                        <li class="grid grid-cols-[70px_1fr_90px] px-4 py-3 text-sm text-gray-800">
                            <span class="font-semibold">{{ $rank }}</span>
                            <span class="truncate">{{ $keyword }}</span>
                            <span class="text-right tabular-nums">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <div
            class="flex items-center justify-between border-t border-gray-200 pt-3 text-sm {{ $hasQuery ? '' : 'hidden' }}"
        >
            <span class="font-semibold text-gray-800">{{ $summaryLabel }}</span>
            <span class="font-medium text-gray-700">{{ $totalCount }}</span>
        </div>
    </section>
</x-modal>
