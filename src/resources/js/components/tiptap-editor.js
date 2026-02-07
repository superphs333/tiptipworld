import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

// 툴바 액션 -> 에디터 명령 매핑.
// 각 키는 Blade 버튼의 `data-tiptap-action` 값과 반드시 동일해야 한다.
const ACTIONS = {
    paragraph: (editor) => editor.chain().focus().setParagraph().run(),
    h2: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run(),
    h3: (editor) => editor.chain().focus().toggleHeading({ level: 3 }).run(),
    bold: (editor) => editor.chain().focus().toggleBold().run(),
    italic: (editor) => editor.chain().focus().toggleItalic().run(),
    strike: (editor) => editor.chain().focus().toggleStrike().run(),
    bulletList: (editor) => editor.chain().focus().toggleBulletList().run(),
    orderedList: (editor) => editor.chain().focus().toggleOrderedList().run(),
    blockquote: (editor) => editor.chain().focus().toggleBlockquote().run(),
    codeBlock: (editor) => editor.chain().focus().toggleCodeBlock().run(),
    undo: (editor) => editor.chain().focus().undo().run(),
    redo: (editor) => editor.chain().focus().redo().run(),
};

// 툴바 액션 -> "현재 활성 상태" 판별 함수 매핑.
// 버튼의 시각 상태(`is-active` 클래스) 토글에 사용된다.
const ACTIVE_STATE = {
    paragraph: (editor) => editor.isActive('paragraph'),
    h2: (editor) => editor.isActive('heading', { level: 2 }),
    h3: (editor) => editor.isActive('heading', { level: 3 }),
    bold: (editor) => editor.isActive('bold'),
    italic: (editor) => editor.isActive('italic'),
    strike: (editor) => editor.isActive('strike'),
    bulletList: (editor) => editor.isActive('bulletList'),
    orderedList: (editor) => editor.isActive('orderedList'),
    blockquote: (editor) => editor.isActive('blockquote'),
    codeBlock: (editor) => editor.isActive('codeBlock'),
};

// ACTIONS에 정의된 액션을 안전하게 실행한다.
// 알 수 없는 액션은 예외를 던지지 않고 무시한다.
function runAction(editor, action) {
    const handler = ACTIONS[action];
    if (!handler) {
        return false;
    }

    return handler(editor);
}

// hidden input 값을 에디터 HTML과 동기화한다.
// 실제로 Laravel 폼 전송 시 서버로 넘어가는 값은 이 hidden input이다.
//
// `emitEvents`:
// - true  -> `input`/`change` 이벤트를 발생시켜 외부 리스너가 반응할 수 있게 함
// - false -> 조용히 값만 갱신(초기 생성 시 불필요한 시작 이벤트 방지)
function syncInputValue(input, editor, emitEvents = true) {
    input.value = editor.isEmpty ? '' : editor.getHTML();

    if (!emitEvents) {
        return;
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

// root에 `.is-empty` 클래스를 토글해 CSS placeholder 표시/숨김을 제어한다.
function updatePlaceholder(root, editor) {
    root.classList.toggle('is-empty', editor.isEmpty);
}

// 툴바 UI 상태를 다시 계산한다.
// 1) 현재 활성 포맷 버튼 표시
// 2) 현재 시점에 실행 불가능한 undo/redo 비활성화
function updateToolbar(root, editor) {
    const buttons = root.querySelectorAll('[data-tiptap-action]');

    buttons.forEach((button) => {
        const action = button.dataset.tiptapAction;
        const isActiveResolver = ACTIVE_STATE[action];
        const isActive = isActiveResolver ? isActiveResolver(editor) : false;

        button.classList.toggle('is-active', isActive);

        if (action === 'undo') {
            button.disabled = !editor.can().chain().focus().undo().run();
            return;
        }

        if (action === 'redo') {
            button.disabled = !editor.can().chain().focus().redo().run();
            return;
        }

        button.disabled = false;
    });
}

// 하나의 `[data-tiptap]` root에 대해 단일 에디터 인스턴스를 마운트한다.
// 이미 마운트된 경우 즉시 반환하는 멱등 함수다.
function mountEditor(root) {
    // 동일 DOM 노드에 에디터 인스턴스가 중복 생성되지 않도록 방지.
    if (root.dataset.tiptapReady === '1') {
        return;
    }

    // Blade 컴포넌트가 만들어주는 필수 DOM 요소.
    const input = root.querySelector('[data-tiptap-input]');
    const editorElement = root.querySelector('[data-tiptap-editor]');

    // 마크업이 불완전하면 조용히 종료.
    if (!input || !editorElement) {
        return;
    }

    // Blade의 `data-disabled` 값에 따른 읽기 전용 모드.
    const isDisabled = root.dataset.disabled === '1' || root.dataset.disabled === 'true';
    // 필요 시에만 hidden input에 input/change 이벤트를 발생시키도록 옵션화.
    // 기본은 false(이벤트 미발행)로 두어 외부 확장 프로그램과의 충돌 가능성을 줄인다.
    const shouldEmitEvents = root.dataset.emitEvents === '1' || root.dataset.emitEvents === 'true';

    // Tiptap 에디터 생성 및 라이프사이클 콜백 연결.
    const editor = new Editor({
        element: editorElement,
        extensions: [StarterKit],
        content: input.value || '',
        editable: !isDisabled,
        onCreate: ({ editor: instance }) => {
            // 초기 생성 시 editor -> hidden input 1회 동기화.
            // 첫 렌더에서 외부 로직이 오작동하지 않도록 이벤트는 발생시키지 않는다.
            syncInputValue(input, instance, false);
            updatePlaceholder(root, instance);
            updateToolbar(root, instance);
        },
        onUpdate: ({ editor: instance }) => {
            // 내용 변경 시 폼 값과 UI 상태를 항상 함께 동기화.
            syncInputValue(input, instance, shouldEmitEvents);
            updatePlaceholder(root, instance);
            updateToolbar(root, instance);
        },
        onSelectionUpdate: ({ editor: instance }) => {
            // 선택 영역이 바뀌면 bold/italic 등 활성 상태가 달라질 수 있다.
            updateToolbar(root, instance);
        },
    });

    // 툴바 버튼 클릭은 이벤트 위임으로 처리.
    // 버튼마다 리스너를 달지 않고 root 한 곳에서만 수신한다.
    const onClick = (event) => {
        const button = event.target.closest('[data-tiptap-action]');
        if (!button || button.disabled) {
            return;
        }

        // 명령 실행 후 버튼 상태를 즉시 갱신.
        runAction(editor, button.dataset.tiptapAction);
        updateToolbar(root, editor);
    };

    // 리스너 등록 + 명시적 언마운트를 위한 teardown 훅 노출.
    root.addEventListener('click', onClick);
    root.dataset.tiptapReady = '1';
    root.__tiptapDestroy = () => {
        // 동적 페이지 전환 시 메모리 누수를 막기 위한 전체 정리.
        root.removeEventListener('click', onClick);
        editor.destroy();
        delete root.__tiptapDestroy;
        root.dataset.tiptapReady = '0';
    };
}

// 지정 컨테이너(기본: document) 내부의 모든 tiptap 인스턴스를 마운트한다.
export function mountTiptapEditors(container = document) {
    container.querySelectorAll('[data-tiptap]').forEach((root) => {
        mountEditor(root);
    });
}

// 지정 컨테이너 내부의 모든 tiptap 인스턴스를 언마운트한다.
// 부분 DOM 교체(AJAX/Turbo 유사 흐름) 전에 유용하다.
export function unmountTiptapEditors(container = document) {
    container.querySelectorAll('[data-tiptap]').forEach((root) => {
        if (typeof root.__tiptapDestroy === 'function') {
            root.__tiptapDestroy();
        }
    });
}

// 일반적인 전체 페이지 로드 시 자동 마운트.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mountTiptapEditors());
} else {
    mountTiptapEditors();
}

// 다른 스크립트에서 수동 제어할 수 있도록 전역 훅 제공.
window.mountTiptapEditors = mountTiptapEditors;
window.unmountTiptapEditors = unmountTiptapEditors;
