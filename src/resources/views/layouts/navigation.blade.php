<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $adminTabs = config('admin.tabs', []);
        $defaultAdminTab = array_key_first($adminTabs) ?? 'users';
        $adminTab = request()->route('tab') ?? request()->query('tab', $defaultAdminTab);
        if (request()->routeIs('admin.tip.*')) {
            $adminTab = 'tips';
        }
        if (!array_key_exists($adminTab, $adminTabs)) {
            $adminTab = $defaultAdminTab;
        }
        $isAdminArea = request()->is('admin*') || request()->routeIs('admin.*');
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @php
                            $isAdmin = Auth::user()->isAdmin();
                        @endphp
                        @if (!$isAdmin || !$isAdminArea)
                            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                {{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                                {{ __('Profile') }}
                            </x-nav-link>
                        @endif
                        @if ($isAdmin && $isAdminArea)
                            <x-admin-nav-links
                                link-component="nav-link"
                                :tabs="$adminTabs"
                                :admin-tab="$adminTab"
                                :is-admin-area="$isAdminArea"
                            />
                        @endif
                    @endauth
                </div>
            </div>

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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @php
                    $isAdmin = Auth::user()->isAdmin();
                @endphp
                @if (!$isAdmin || !$isAdminArea)
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                @endif
                @if ($isAdmin && $isAdminArea)
                    <x-admin-nav-links
                        link-component="responsive-nav-link"
                        :tabs="$adminTabs"
                        :admin-tab="$adminTab"
                        :is-admin-area="$isAdminArea"
                    />
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
