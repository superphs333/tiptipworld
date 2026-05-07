@if ($socialProviderButtons !== [])
    <section class="mt-8" aria-label="{{ $mode === 'register' ? '소셜 회원가입' : '소셜 로그인' }}">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400">
                <span class="bg-white px-4">또는</span>
            </div>
        </div>

        <div class="mt-5 space-y-3">
            @foreach ($socialProviderButtons as $button)
                <a
                    href="{{ $button['href'] }}"
                    aria-label="{{ $button['label'] }}"
                    class="{{ $button['class'] }} group flex w-full items-center gap-4 rounded-2xl border px-4 py-3.5 shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-offset-2"
                >
                    <span class="{{ $button['icon_class'] }} flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm">
                        @switch($button['key'])
                            @case('google')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#4285F4" d="M21.81 12.23c0-.72-.06-1.25-.19-1.8H12.2v3.56h5.53c-.11.88-.72 2.21-2.07 3.1l-.02.12 3 2.28.21.02c1.96-1.78 3.09-4.39 3.09-7.28Z"/>
                                    <path fill="#34A853" d="M12.2 22c2.71 0 4.99-.88 6.66-2.39l-3.18-2.42c-.85.58-1.98.99-3.48.99-2.65 0-4.89-1.72-5.69-4.09l-.12.01-3.12 2.37-.04.11A10.08 10.08 0 0 0 12.2 22Z"/>
                                    <path fill="#FBBC05" d="M6.51 14.09A5.92 5.92 0 0 1 6.18 12c0-.73.13-1.43.32-2.09l-.01-.14-3.16-2.4-.1.05A9.83 9.83 0 0 0 2.2 12c0 1.61.39 3.13 1.08 4.42l3.23-2.33Z"/>
                                    <path fill="#EA4335" d="M12.2 5.82c1.88 0 3.14.8 3.86 1.46l2.82-2.69C17.18 3.02 14.91 2 12.2 2a10.08 10.08 0 0 0-8.97 5.42l3.27 2.49c.82-2.37 3.05-4.09 5.7-4.09Z"/>
                                </svg>
                                @break

                            @case('kakao')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="currentColor" d="M12 4.5c-4.8 0-8.69 3.05-8.69 6.81 0 2.43 1.62 4.56 4.06 5.76l-1.03 3.77a.29.29 0 0 0 .45.31l4.48-2.96c.24.02.48.04.73.04 4.8 0 8.69-3.05 8.69-6.81S16.8 4.5 12 4.5Z"/>
                                </svg>
                                @break

                            @default
                                <span class="text-base font-bold">
                                    {{ $button['icon'] }}
                                </span>
                        @endswitch
                    </span>

                    <span class="min-w-0 flex-1 text-left">
                        <span class="block text-sm font-semibold leading-5">
                            {{ $button['label'] }}
                        </span>
                    </span>

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white/80 opacity-60 transition duration-200 group-hover:translate-x-0.5 group-hover:opacity-100">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M7.5 4.17 13.33 10l-5.83 5.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
