<header class="sticky top-0 z-40 border-b border-gray-300 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3 text-gray-900">
                <x-application-logo class="h-9 w-9 rounded-full ring-1 ring-gray-300" alt="팁팁월드 로고" />
                <span class="text-lg font-semibold tracking-tight">TipTipWorld</span>
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:text-gray-900" aria-label="검색">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                        </svg>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" aria-expanded="false" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:border-gray-400">
                            <img src="{{ Auth::user()->profile_image_url ?? asset('images/avatar-default.svg') }}" alt="프로필 이미지" class="h-6 w-6 shrink-0 rounded-full ring-1 ring-gray-300" />
                            <span>{{ Auth::user()->name }}</span>
                        </button>
                        <div x-show="open" @click.outside="open = false" class="absolute end-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg">
                            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm text-gray-800 hover:bg-gray-100">프로필</a>
                            <a href="#" class="block px-3 py-2 text-sm text-gray-800 hover:bg-gray-100">북마크</a>
                            <a href="#" class="block px-3 py-2 text-sm text-gray-800 hover:bg-gray-100">알림</a>
                            <div class="border-t border-gray-200"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-gray-800 hover:bg-gray-100">
                                    sign out
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900">로그인</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700">
                        가입하기
                    </a>
                @endguest
            </div>
        </div>
    </div>
</header>
