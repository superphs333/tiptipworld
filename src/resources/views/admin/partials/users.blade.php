@php
    $users = $datas;
    $totalCount = method_exists($users, 'total') ? $users->total() : $users->count();
    $showPagination = method_exists($users, 'links');
@endphp

<div x-data="userPanel()" @keydown.escape.window="closeModal()">
    <div class="category-panel user-panel">
        <div class="category-panel__content">
            {{-- 필터부분 --}}
            <div class="category-panel__filter user-panel__filter">
                <form class="category-panel__form user-panel__form" action="" method="GET">
                    @if (request()->has('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}" />
                    @endif
                    <div class="user-panel__filters">
                        <div class="user-panel__filter-row">
                            {{-- 상태 --}}
                            <div class="user-panel__filter-group">
                                <span class="user-panel__filter-label">상태</span>
                                <div
                                    class="category-panel__select-wrap user-panel__select-wrap"
                                    x-data="selectBox()"
                                    :class="{ 'is-open': open }"
                                    @click.outside="close()"
                                    @keydown.escape.stop="close()"
                                >
                                    <select class="category-panel__select-native" name="status" x-ref="select" x-model="value">
                                        <option value="" @selected(blank(request('status')))>전체</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                        <span class="category-panel__select-label" x-text="label">전체</span>
                                        <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                        <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">전체</li>
                                        @foreach($statuses as $status)
                                            <li class="category-panel__select-option" role="option" @click="choose(@js($status))" :class="{ 'is-active': value === @js($status) }" :aria-selected="value === @js($status)">{{ $status }}</li>
                                        @endforeach                                        
                                    </ul>
                                </div>
                            </div>

                            {{-- 역할 --}}
                            <div class="user-panel__filter-group">
                                <span class="user-panel__filter-label">역할</span>
                                <div
                                    class="category-panel__select-wrap user-panel__select-wrap"
                                    x-data="selectBox()"
                                    :class="{ 'is-open': open }"
                                    @click.outside="close()"
                                    @keydown.escape.stop="close()"
                                >
                                    <select class="category-panel__select-native" name="role" x-ref="select" x-model="value">
                                        <option value="" @selected(blank(request('role')))>전체</option>
                                        @foreach($roles as $role)
                                            @php
                                                $roleKey = $role->key ?? $role->name ?? (string) $role;
                                                $roleLabel = $role->name ?? $role->key ?? (string) $role;
                                            @endphp
                                            <option value="{{ $roleKey }}" @selected(request('role') === $roleKey)>{{ $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                    <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                        <span class="category-panel__select-label" x-text="label">전체</span>
                                        <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                        <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">전체</li>
                                        @foreach($roles as $role)
                                            @php
                                                $roleKey = $role->key ?? $role->name ?? (string) $role;
                                                $roleLabel = $role->name ?? $role->key ?? (string) $role;
                                            @endphp
                                            <li
                                                class="category-panel__select-option"
                                                role="option"
                                                @click="choose(@js($roleKey))"
                                                :class="{ 'is-active': value === @js($roleKey) }"
                                                :aria-selected="value === @js($roleKey)"
                                            >{{ $roleLabel }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="user-panel__filter-row user-panel__filter-row--search">
                            <input class="category-panel__input user-panel__search-input" type="text" name="query" placeholder="이름/이메일 검색" value="{{ request('query') }}" />
                            <button class="category-panel__search-btn" type="submit" aria-label="검색">
                                <svg class="category-panel__search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M11 19a8 8 0 1 1 5.657-2.343L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="user-panel__list-header">
                <div class="user-panel__list-title">목록</div>
                <form class="user-panel__display-form" action="" method="GET">
                    @php
                        $displayParams = ['tab', 'status', 'role', 'query'];
                    @endphp
                    @foreach ($displayParams as $param)
                        @if (request()->has($param))
                            <input type="hidden" name="{{ $param }}" value="{{ request($param) }}" />
                        @endif
                    @endforeach
                    <span class="user-panel__display-label">표시설정</span>
                    <label class="user-panel__display-control" for="users-per-page">
                        <span>페이지당</span>
                        <input
                            class="category-panel__input user-panel__per-page-input"
                            type="number"
                            id="users-per-page"
                            name="per_page"
                            min="1"
                            max="100"
                            step="1"
                            value="{{ request('per_page', 20) }}"
                        />
                    </label>
                    <button class="category-panel__bulk-btn" type="submit">적용</button>
                </form>
            </div>

            {{-- 리스트 부분  --}}
            <div class="user-panel__list">
                <table class="user-panel__table">
                    <thead>
                        <tr>
                            <th>사용자(이름/ID)</th>
                            <th>연결된 계정</th>
                            <th>상태</th>
                            <th>역할</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $name = data_get($user, 'name', '-') ?: '-';
                                $email = data_get($user, 'email', '-') ?: '-';
                                $socialAccounts = collect($user->socialAccounts ?? []);
                                $connectedAccountBadges = $socialAccounts
                                    ->map(function ($account): array {
                                        $provider = (string) ($account->provider ?? 'social');

                                        return [
                                            'provider' => $provider,
                                            'label' => match ($provider) {
                                                'google' => 'g',
                                                'kakao' => 'k',
                                                default => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::substr($provider, 0, 1)) ?: '?',
                                            },
                                        ];
                                    })
                                    ->values();
                                $statusRaw = data_get($user, 'status', data_get($user, 'is_active', 'active'));
                                if ($statusRaw === false || $statusRaw === 0 || $statusRaw === '0' || $statusRaw === 'inactive') {
                                    $statusValue = 'suspended';
                                    $statusLabel = 'suspended';
                                } elseif (is_string($statusRaw) && $statusRaw !== '') {
                                    $statusValue = $statusRaw;
                                    $statusLabel = $statusRaw;
                                } else {
                                    $statusValue = 'active';
                                    $statusLabel = 'active';
                                }
                                $roleEntries = collect();
                                if (is_object($user) && method_exists($user, 'relationLoaded') && $user->relationLoaded('roles')) {
                                    $roleEntries = $user->roles->map(fn ($role) => $role->key ?? $role->name ?? (string) $role);
                                } elseif (is_array($user)) {
                                    $roleEntries = collect($user['roles'] ?? []);
                                } elseif (is_object($user) && property_exists($user, 'roles')) {
                                    $roleEntries = collect($user->roles ?? []);
                                }
                                $roleEntries = $roleEntries
                                    ->map(fn ($role) => is_string($role) ? $role : (data_get($role, 'key') ?? data_get($role, 'name') ?? (string) $role))
                                    ->filter()
                                    ->values();
                                $initial = $name !== '-' ? mb_substr($name, 0, 1) : '🙂';
                            @endphp
                            <tr>
                                <td>
                                    <div class="user-panel__user">
                                        <span class="user-panel__avatar" aria-hidden="true">{{ $initial }}</span>
                                        <div class="user-panel__user-meta">
                                            <div class="user-panel__user-name">{{ $name }}</div>
                                            <div class="user-panel__user-sub">{{ $email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($connectedAccountBadges->isNotEmpty())
                                        <div class="user-panel__account-badges">
                                            @foreach ($connectedAccountBadges as $badge)
                                                <span
                                                    class="user-panel__account-badge user-panel__account-badge--{{ $badge['provider'] }}"
                                                    title="{{ $badge['provider'] }}"
                                                    aria-label="{{ $badge['provider'] }}"
                                                >{{ $badge['label'] }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="user-panel__pill user-panel__pill--status user-panel__pill--{{ $statusValue }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    <div class="user-panel__roles">
                                            @foreach ($roleEntries as $role)
                                                <span class="user-panel__pill user-panel__pill--role user-panel__pill--{{ $role }}">{{ $role }}</span>
                                            @endforeach
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="user-panel__edit-btn" @click="openModal(@js($user))">
                                        <svg class="user-panel__edit-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-linejoin="round"/>
                                            <path d="M14 6l4 4" stroke="currentColor" stroke-linecap="round"/>
                                        </svg>
                                        <span>편집</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="user-panel__empty-row">
                                <td class="user-panel__empty" colspan="5">데이터가 없습니다.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>합계</td>
                            <td colspan="3"></td>
                            <td class="user-panel__total">{{ $totalCount }}명</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($showPagination)
                <div class="app-pagination">
                    {{ $users->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif

            <div class="user-panel__note">* 사용자는 가입으로 생성됩니다. 관리는 편집에서 상태/역할을 변경합니다.</div>
        </div>
    </div>

    {{-- 모달부분 --}}
    <div class="category-modal user-modal" :class="{ 'is-open': modalOpen }" :aria-hidden="(!modalOpen).toString()">
        <div class="category-modal__overlay" @click="closeModal()"></div>
        <div class="category-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="category-modal__header">
                <h3 class="category-modal__title" id="user-modal-title">사용자 편집</h3>
                <button class="category-modal__close" type="button" aria-label="닫기" @click="closeModal()">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form class="category-modal__form user-modal__form" 
                :action="`{{ route('admin.user.update', ['user_id' => '__ID__']) }}`.replace('__ID__', data.id)" 
                method="POST">
                @csrf
                @method('PATCH')
                <div class="user-modal__profile">
                    <div class="user-modal__avatar">
                        <img :src="data.profile_image_url || data.avatar || avatarFallback" alt="" />
                    </div>
                </div>

                <div class="category-modal__grid user-modal__grid">
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="user-name">이름</label>
                        <input class="category-modal__input" type="text" name="name" id="user-name" x-ref="modalFocus" x-model="data.name" />
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="user-email">이메일 </label>
                        <input class="category-modal__input user-modal__input--readonly" type="email" id="user-email" x-model="data.email" readonly />
                    </div>
                    <div class="category-modal__field category-modal__field--full">
                        <label class="category-modal__label" for="user-connected-accounts">연결된 계정</label>
                        <div class="category-modal__input user-modal__input--readonly user-modal__account-summary" id="user-connected-accounts">
                            <template x-if="data.connected_account_badges.length > 0">
                                <div class="user-panel__account-badges">
                                    <template x-for="(badge, index) in data.connected_account_badges" :key="`${badge.provider}-${index}`">
                                        <span
                                            class="user-panel__account-badge"
                                            :class="`user-panel__account-badge--${badge.provider}`"
                                            :title="badge.provider"
                                            :aria-label="badge.provider"
                                            x-text="badge.label"
                                        ></span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="data.connected_account_badges.length === 0">
                                <span>-</span>
                            </template>
                        </div>
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="user-status">상태</label>
                        <div
                            class="category-modal__select-wrap"
                            x-data="selectBox()"
                            x-modelable="value"
                            x-model="data.status"
                            :class="{ 'is-open': open }"
                            @click.outside="close()"
                            @keydown.escape.stop="close()"
                        >
                            <select class="category-modal__select-native" id="user-status" name="status" x-ref="select" x-model="value">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            <button class="category-modal__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                <span class="category-modal__select-label" x-text="label">active</span>
                                <svg class="category-modal__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul class="category-modal__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                @foreach($statuses as $status)
                                    <li
                                        class="category-modal__select-option"
                                        role="option"
                                        @click="choose(@js($status))"
                                        :class="{ 'is-active': value === @js($status) }"
                                        :aria-selected="value === @js($status)"
                                    >{{ $status }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label">역할</label>
                        <div class="user-modal__roles">
                            @foreach($roles as $role)
                                @php
                                    $roleKey = $role->id ?? $role->key ?? $role->name ?? (string) $role;
                                    $roleLabel = $role->name ?? $role->key ?? (string) $role;
                                @endphp
                                <label class="user-modal__role-option">
                                    <input type="checkbox" name="roles[]" value="{{ $roleKey }}" x-model="data.roles" />
                                    <span>{{ $roleLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- <div class="user-modal__meta-toggle">
                    <button class="user-modal__toggle" type="button" @click="metaOpen = !metaOpen" :aria-expanded="metaOpen">
                        소셜메타 보기
                        <svg class="user-modal__toggle-icon" :class="{ 'is-open': metaOpen }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="user-modal__meta" x-show="metaOpen" x-cloak>
                    <textarea class="category-modal__textarea user-modal__textarea" readonly x-model="data.social_meta"></textarea>
                </div> --}}

                <div class="category-modal__actions">
                    <button class="category-modal__btn" type="button" @click="closeModal()">닫기</button>
                        <button class="category-modal__btn category-modal__btn--primary" type="submit">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("userPanel", () => ({
            modalOpen: false,
            metaOpen: false,
            avatarFallback: @js(asset('images/avatar-default.svg')),
            initData: {
                id: null,
                name: "",
                email: "",
                connected_account_badges: [],
                status: "active",
                roles: [],
                social_accounts: [],
                social_meta: "-",
                profile_image_url: "",
            },
            data: {},
            init() {
                this.$watch("modalOpen", (value) => {
                    document.body.classList.toggle("is-modal-open", value);
                    if (value) {
                        this.$nextTick(() => this.$refs.modalFocus?.focus());
                    }
                });
                this.data = this.normalizeUser();
            },
            normalizeUser(user = null) {
                const normalized = { ...this.initData, ...(user ?? {}) };
                const rawStatus = normalized.status ?? normalized.is_active ?? "active";
                const socialAccounts = Array.isArray(normalized.social_accounts)
                    ? normalized.social_accounts
                    : [];

                if (rawStatus === false || rawStatus === 0 || rawStatus === "0" || rawStatus === "inactive") {
                    normalized.status = "suspended";
                } else if (typeof rawStatus === "string" && rawStatus !== "") {
                    normalized.status = rawStatus;
                } else if (rawStatus === true || rawStatus === 1 || rawStatus === "1") {
                    normalized.status = "active";
                } else {
                    normalized.status = "active";
                }

                if (socialAccounts.length > 0) {
                    normalized.connected_account_badges = socialAccounts
                        .map((account) => {
                            const provider = account?.provider || "social";

                            return {
                                provider,
                                label: provider === "google"
                                    ? "g"
                                    : provider === "kakao"
                                        ? "k"
                                        : String(provider).slice(0, 1).toLowerCase() || "?",
                            };
                        });

                    normalized.social_meta = JSON.stringify(
                        socialAccounts.map((account) => ({
                            provider: account?.provider || null,
                            provider_user_id: account?.provider_user_id || null,
                            meta: account?.meta || null,
                        })),
                        null,
                        2,
                    );
                } else {
                    normalized.connected_account_badges = Array.isArray(normalized.connected_account_badges)
                        ? normalized.connected_account_badges
                        : [];
                    normalized.social_meta = normalized.social_meta || "-";
                }

                const roles = normalized.roles ?? [];
                if (Array.isArray(roles)) {
                    normalized.roles = roles
                        .map((role) => {
                            if (typeof role === "string") return role;
                            if (role && typeof role === "object") {
                                return role.id || role.key || role.name || "";
                            }
                            return "";
                        })
                        .filter(Boolean);
                } else if (typeof roles === "string") {
                    normalized.roles = [roles];
                } else {
                    normalized.roles = [];
                }

                if (normalized.social_meta && typeof normalized.social_meta === "object") {
                    try {
                        normalized.social_meta = JSON.stringify(normalized.social_meta, null, 2);
                    } catch (error) {
                        normalized.social_meta = String(normalized.social_meta);
                    }
                }

                if (!normalized.social_meta) {
                    normalized.social_meta = "-";
                }

                return normalized;
            },
            openModal(user) {
                this.data = this.normalizeUser(user);
                this.metaOpen = false;
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
            },
        }));

        Alpine.data("selectBox", () => ({
            open: false,
            value: "",
            label: "",
            init() {
                if (this.value === "" || this.value === null) {
                    this.value = this.$refs.select?.value ?? "";
                }
                this.setLabel();
                this.$watch("value", () => this.setLabel());
            },
            setLabel() {
                const options = Array.from(this.$refs.select?.options || []);
                const selected = options.find((option) => option.value === this.value);
                this.label = selected ? selected.textContent : "";
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.menu?.focus());
                }
            },
            choose(value) {
                this.value = value;
                this.open = false;
            },
            close() {
                this.open = false;
            },
        }));
    });
</script>
@endonce
