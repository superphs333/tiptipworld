<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class Tiptap extends Component
{
    // 폼 전송용 필드 이름(name 속성).
    public string $name;

    // 컴포넌트 루트/내부 요소를 식별하기 위한 고유 id.
    public string $id;

    // 초기 에디터 값(HTML).
    public ?string $value;

    // 에디터가 비어 있을 때 표시할 placeholder 문구.
    public string $placeholder;

    // 에디터 최소 높이(CSS 값).
    public string $minHeight;

    // true면 읽기 전용처럼 툴바를 숨기고 편집을 막는다.
    public bool $disabled;

    // true면 hidden input에 required 속성을 부여한다.
    public bool $required;

    // true면 content 변경 시 input/change 이벤트를 발생시킨다.
    public bool $emitEvents;

    /**
     * Tiptap Blade 컴포넌트 생성자.
     * 전달받은 옵션을 바탕으로 렌더링/초기화에 필요한 상태를 보관한다.
     */
    public function __construct(
        string $name,
        ?string $id = null,
        ?string $value = null,
        string $placeholder = '내용을 입력하세요.',
        string $minHeight = '240px',
        bool $disabled = false,
        bool $required = false,
        bool $emitEvents = false,
    ) {
        $this->name = $name;
        $this->id = $id ?: $this->makeId($name);
        $this->value = $value;
        $this->placeholder = $placeholder;
        $this->minHeight = $minHeight;
        $this->disabled = $disabled;
        $this->required = $required;
        $this->emitEvents = $emitEvents;
    }

    /**
     * 렌더링할 Blade 뷰를 반환한다.
     */
    public function render(): View
    {
        return view('components.tiptap');
    }

    /**
     * hidden input 요소 id를 생성한다.
     */
    public function hiddenInputId(): string
    {
        return "{$this->id}_input";
    }

    /**
     * 실제 에디터 컨테이너 요소 id를 생성한다.
     */
    public function editorId(): string
    {
        return "{$this->id}_editor";
    }

    /**
     * 폼 재전송 시 old 값 우선, 없으면 초기 value를 사용해 에디터 값을 결정한다.
     */
    public function fieldValue(): string
    {
        $value = old($this->name, $this->value);

        return is_string($value) ? $value : '';
    }

    /**
     * name 값을 HTML id로 안전하게 쓸 수 있도록 정규화한 뒤 랜덤 접미사를 붙인다.
     */
    private function makeId(string $name): string
    {
        $sanitized = Str::of($name)
            ->replace(['[', ']', '.'], '_')
            ->trim('_')
            ->value();

        return $sanitized . '_' . Str::lower(Str::random(6));
    }
}
