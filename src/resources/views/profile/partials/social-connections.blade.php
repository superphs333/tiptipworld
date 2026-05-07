<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            소셜 연동
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            로그인 가능한 소셜 계정을 연결해 두면 계정 접근 수단을 늘릴 수 있습니다.
        </p>
    </header>

    @if ($socialConnectionCards === [])
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-600">
            {{ $socialConnectionEmptyStateMessage }}
        </div>
    @else
        <div class="space-y-3">
            @foreach ($socialConnectionCards as $card)
                <div class="{{ $card['card_class'] }} rounded-2xl border p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="{{ $card['icon_class'] }} flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold shadow-sm">
                                {{ $card['icon'] }}
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        {{ $card['name'] }}
                                    </h3>
                                    <span class="{{ $card['status']['class'] }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium">
                                        {{ $card['status']['label'] }}
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $card['description'] }}
                                </p>

                                @if ($card['synced_at'] !== null)
                                    <p class="mt-2 text-xs text-gray-500">
                                        최근 동기화 {{ $card['synced_at'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            @if ($card['action']['type'] === 'link')
                                <a
                                    href="{{ $card['action']['href'] }}"
                                    class="{{ $card['action']['class'] }}"
                                >
                                    {{ $card['action']['label'] }}
                                </a>
                            @elseif ($card['action']['type'] === 'unlink')
                                <form method="post" action="{{ $card['action']['action'] }}">
                                    @csrf
                                    @method('delete')
                                    <input type="hidden" name="return_to" value="{{ $card['action']['return_to'] }}">

                                    <button type="submit" class="{{ $card['action']['class'] }}">
                                        {{ $card['action']['label'] }}
                                    </button>
                                </form>
                            @else
                                <span class="{{ $card['action']['class'] }}">
                                    {{ $card['action']['label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($socialConnectionMessages !== [])
        <x-input-error :messages="$socialConnectionMessages" />
    @endif

    @if ($socialConnectionStatus !== null)
        <p class="{{ $socialConnectionStatus['class'] }}">
            {{ $socialConnectionStatus['message'] }}
        </p>
    @endif
</section>
