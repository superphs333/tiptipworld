@php
    $formAction = $formAction ?? '';
    $backUrl = $backUrl ?? route('admin', ['tab' => 'tips']);
    $submitLabel = $submitLabel ?? '게시하기';
@endphp

<div x-data="tipCreate()">
    <div class="category-panel tip-create">
        <div class="category-panel__content tip-create__content">
            <form class="tip-create__form" action="{{ $formAction }}" method="POST">
                @csrf

                <div class="tip-create__sticky-bar">
                    <div class="tip-create__sticky-left">
                        <a class="category-panel__bulk-btn category-panel__bulk-btn--ghost tip-create__back" href="{{ $backUrl }}">← 목록</a>
                        <div class="tip-create__sticky-selects">
                            <div class="tip-create__sticky-group">
                                <span class="tip-create__sticky-label">상태:</span>
                                <div
                                    class="category-panel__select-wrap tip-create__select"
                                    x-data="selectBox()"
                                    :class="{ 'is-open': open }"
                                    @click.outside="close()"
                                    @keydown.escape.stop="close()"
                                >
                                    <select class="category-panel__select-native" name="status" x-ref="select" x-model="value">
                                        @foreach(config('app.tip_status', []) as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach

                                    </select>
                                    <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                        <span class="category-panel__select-label" x-text="label">draft</span>
                                        <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                        @foreach(config('app.tip_status', []) as $status)
                                            <li class="category-panel__select-option" role="option" @click="choose('{{ $status }}')" :class="{ 'is-active': value === '{{ $status }}' }" :aria-selected="value === '{{ $status }}'">{{ $status }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="tip-create__sticky-group">
                                <span class="tip-create__sticky-label">노출:</span>
                                <div
                                    class="category-panel__select-wrap tip-create__select"
                                    x-data="selectBox()"
                                    :class="{ 'is-open': open }"
                                    @click.outside="close()"
                                    @keydown.escape.stop="close()"
                                >
                                    <select class="category-panel__select-native" name="visibility" x-ref="select" x-model="value">
                                        @foreach(config('app.tip_visibility', []) as $visibility)
                                            <option value="{{ $visibility }}">{{ $visibility }}</option>
                                        @endforeach
                                    </select>
                                    <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                        <span class="category-panel__select-label" x-text="label">public</span>
                                        <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                        @foreach(config('app.tip_visibility', []) as $visibility)
                                            <li class="category-panel__select-option" role="option" @click="choose('{{ $visibility }}')" :class="{ 'is-active': value === '{{ $visibility }}' }" :aria-selected="value === '{{ $visibility }}'">{{ $visibility }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tip-create__sticky-actions">
                        <button class="category-panel__bulk-btn" type="button">임시저장</button>
                        <button class="category-panel__bulk-btn category-panel__bulk-btn--ghost" type="button">미리보기</button>
                        <button class="category-panel__bulk-btn category-panel__bulk-btn--accent" type="submit">{{ $submitLabel }}</button>
                    </div>
                </div>

                <div class="tip-create__grid">
                    <div class="tip-create__main">
                        <section class="tip-create__card">
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">제목</h3>
                                    <p class="tip-create__card-desc">최대 120자</p>
                                </div>
                                <div class="tip-create__counter" x-text="`${title.length}/120`">0/120</div>
                            </div>
                            <input
                                class="category-panel__input"
                                type="text"
                                name="title"
                                maxlength="120"
                                placeholder="제목을 입력하세요"
                                x-model="title"
                            />
                        </section>

                        <section class="tip-create__card">
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">요약</h3>
                                    <p class="tip-create__card-desc">리스트용 요약 (최대 255자)</p>
                                </div>
                                <div class="tip-create__counter" x-text="`${excerpt.length}/255`">0/255</div>
                            </div>
                            <textarea
                                class="category-panel__input tip-create__textarea"
                                name="excerpt"
                                maxlength="255"
                                rows="4"
                                placeholder="본문 첫 문장으로 자동 생성하려면 비워두세요."
                                x-model="excerpt"
                            ></textarea>
                            <label class="tip-create__checkbox">
                                <input type="checkbox" name="excerpt_auto" checked />
                                <span>본문 첫 문장으로 자동 생성</span>
                            </label>
                        </section>

                        <section class="tip-create__card">
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">본문</h3>
                                </div>
                            </div>
                            <x-summernote name="content" />
                        </section>
                    </div>

                    <aside class="tip-create__side">
                        <section class="tip-create__card">
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">카테고리</h3>                                    
                                </div>
                            </div>
                            <div
                                class="category-panel__select-wrap tip-create__select tip-create__select--full"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="category_id" x-ref="select" x-model="value">
                                    <option value="">카테고리 선택</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">카테고리 선택</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">카테고리 선택</li>
                                    @foreach($categories as $category)
                                        <li class="category-panel__select-option" role="option" @click="choose('{{ $category->id }}')" :class="{ 'is-active': value === '{{ $category->id }}' }" :aria-selected="value === '{{ $category->id }}'">{{ $category->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </section>

                        <section class="tip-create__card">
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">썸네일</h3>
                                    <p class="tip-create__card-desc">이미지 업로드</p>
                                </div>
                            </div>
                            <div class="tip-create__thumb">
                                <div class="tip-create__thumb-preview">
                                    <template x-if="thumbnailPreviewUrl">
                                        <img class="tip-create__thumb-image" :src="thumbnailPreviewUrl" alt="썸네일 미리보기" />
                                    </template>
                                    <template x-if="!thumbnailPreviewUrl">
                                        <span>미리보기 썸네일</span>
                                    </template>
                                </div>
                                <div class="tip-create__thumb-actions">
                                    <input
                                        class="tip-create__file"
                                        type="file"
                                        name="thumbnail"
                                        accept="image/*"
                                        x-ref="thumbnailInput"
                                        @change="onThumbnailChange($event)"
                                    />
                                    <div class="tip-create__thumb-buttons">
                                        <button class="category-panel__bulk-btn" type="button" @click="clearThumbnail()">제거</button>
                                        <button class="category-panel__bulk-btn category-panel__bulk-btn--ghost" type="button" @click="openThumbnailPicker()">변경</button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- 태그부분 --}}
                        <section class="tip-create__card">
                            {{-- 헤드 --}}
                            <div class="tip-create__card-header">
                                <div>
                                    <h3 class="tip-create__card-title">태그</h3>
                                </div>
                                <div class="tip-create__counter" x-text="`총 ${selectedTags.length}개`">총 0개</div>
                            </div>
                            {{-- 태그 입력부 --}}
                            <div class="tip-create__tag-input">
                                <input
                                    class="category-panel__input"
                                    type="text"
                                    name="tag_input"
                                    placeholder="태그 검색/입력"
                                    x-model="tagInput"
                                    @keydown.enter.prevent="addTag()"
                                />
                                <button class="category-panel__bulk-btn" type="button" @click="addTag()">추가</button>
                            </div>
                            {{-- 선택된 태그 표시 --}}
                            <div class="tip-create__tags">
                                <template x-if="selectedTags.length === 0">
                                    <span class="tip-create__tags-empty">선택된 태그 없음</span>
                                </template>
                                <template x-for="tag in selectedTags" :key="tag">
                                    <span class="tip-create__tag">
                                        <span x-text="`#${tag}`"></span>
                                        <button type="button" class="tip-create__tag-remove" @click="removeTag(tag)">×</button>
                                    </span>
                                </template>
                            </div>
                        </section>
                    </aside>
                </div>

            </form>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
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

        Alpine.data("tipCreate", () => ({
            title: "",
            excerpt: "",
            tagInput: "",
            selectedTags: [],
            thumbnailPreviewUrl: "",
            addTag() {
                const value = this.tagInput.trim().replace(/^#/, "");
                if (!value) return;
                if (!this.selectedTags.includes(value)) {
                    this.selectedTags.push(value);
                }
                this.tagInput = "";
            },
            removeTag(tag) {
                this.selectedTags = this.selectedTags.filter((item) => item !== tag);
            },
            openThumbnailPicker() {
                this.$refs.thumbnailInput?.click();
            },
            onThumbnailChange(event) {
                const file = event?.target?.files?.[0] ?? null;

                if (this.thumbnailPreviewUrl) {
                    URL.revokeObjectURL(this.thumbnailPreviewUrl);
                    this.thumbnailPreviewUrl = "";
                }

                if (!file) {
                    return;
                }

                if (!file.type || !file.type.startsWith("image/")) {
                    event.target.value = "";
                    return;
                }

                this.thumbnailPreviewUrl = URL.createObjectURL(file);
            },
            clearThumbnail() {
                if (this.thumbnailPreviewUrl) {
                    URL.revokeObjectURL(this.thumbnailPreviewUrl);
                }

                this.thumbnailPreviewUrl = "";

                if (this.$refs.thumbnailInput) {
                    this.$refs.thumbnailInput.value = "";
                }
            },
        }));
    });
</script>
@endonce
