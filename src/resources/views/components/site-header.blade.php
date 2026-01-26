<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-slate-900">
                <img src="{{ asset('images/logo-round.svg') }}" alt="팁팁월드 로고" class="h-9 w-9 rounded-full ring-1 ring-slate-200" />
                <span class="text-lg font-semibold tracking-tight">TipTipWorld</span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('home') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        글쓰기
                    </a>
                    <a href="{{ route('profile.edit') }}" class="hidden sm:inline-flex items-center rounded-full border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:border-slate-300 hover:text-slate-900">
                        내 프로필
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="text-sm text-slate-600 hover:text-slate-900">
                            로그아웃
                        </button>
                    </form>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900">로그인</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        가입하기
                    </a>
                @endguest
            </div>
        </div>

        <div class="border-t border-slate-100 py-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <form class="relative w-full md:max-w-md" role="search">
                    <label class="sr-only" for="site-search">검색</label>
                    <input
                        id="site-search"
                        name="q"
                        type="search"
                        placeholder="토픽/태그/멤버 검색"
                        class="w-full rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    />
                </form>

                <nav class="flex gap-4 overflow-x-auto text-sm text-slate-600 whitespace-nowrap md:flex-wrap md:overflow-visible md:whitespace-normal">
                    <a href="{{ route('home') }}" class="shrink-0 hover:text-slate-900">홈</a>
                    <a href="#" class="shrink-0 hover:text-slate-900">게시판</a>
                    <a href="#" class="shrink-0 hover:text-slate-900">인기글</a>
                    <a href="#" class="shrink-0 hover:text-slate-900">질문방</a>
                    <a href="#" class="shrink-0 hover:text-slate-900">이벤트</a>
                </nav>
            </div>
        </div>
    </div>
</header>
