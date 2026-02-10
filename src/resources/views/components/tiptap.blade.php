@php
    // 서버에서 전달받은 초기값(또는 old 입력값)을 한 번 계산해 재사용한다.
    $currentValue = $fieldValue();
@endphp

@once
    @vite('resources/js/components/tiptap-editor.js')
@endonce

{{-- Tiptap 컴포넌트 루트.
     - `data-tiptap`: JS 마운트 대상 식별자
     - `is-empty`: 초기값이 비어 있으면 placeholder 표시를 위한 클래스 --}}
<div
    {{ $attributes->class(['tiptap-field', 'is-empty' => blank($currentValue)]) }}
    data-tiptap
    @if($disabled) data-disabled="1" @endif
    @if($emitEvents) data-emit-events="1" @endif
>
    {{-- 실제 폼 전송 값.
         에디터 내용은 JS에서 HTML 문자열로 이 hidden input에 동기화된다. --}}
    <input
        id="{{ $hiddenInputId() }}"
        type="hidden"
        name="{{ $name }}"
        value="{{ $currentValue }}"
        data-tiptap-input
        @if($required) required @endif
    />

    {{-- 읽기 전용 모드가 아닐 때만 툴바 노출.
         `data-tiptap-action` 값은 JS ACTIONS 키와 1:1로 매칭된다. --}}
    @unless($disabled)
        <div class="tiptap-field__toolbar" role="toolbar" aria-label="Tiptap toolbar">
            <button class="tiptap-field__tool" type="button" data-tiptap-action="paragraph">P</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="h2">H2</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="h3">H3</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="bold"><strong>B</strong></button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="italic"><em>I</em></button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="strike"><s>S</s></button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="bulletList">UL</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="orderedList">OL</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="blockquote">"</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="codeBlock">{ }</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="undo">Undo</button>
            <button class="tiptap-field__tool" type="button" data-tiptap-action="redo">Redo</button>
        </div>
    @endunless

    {{-- 에디터 렌더링 영역.
         - placeholder는 내용이 비어 있을 때 CSS/JS로 표시
         - 실제 contenteditable DOM은 JS가 `data-tiptap-editor`에 마운트 --}}
    <div class="tiptap-field__editor-wrap">
        <div class="tiptap-field__placeholder" data-tiptap-placeholder>{{ $placeholder }}</div>
        <div
            id="{{ $editorId() }}"
            class="tiptap-field__editor"
            data-tiptap-editor
            style="min-height: {{ $minHeight }};"
        ></div>
    </div>
</div>
