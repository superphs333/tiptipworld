<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class Summernote extends Component
{
    public string $name;
    public string $id;
    public ?string $value;
    public string $placeholder;
    public int $height;

    public function __construct(
        string $name,
        ?string $id = null,
        ?string $value = null,
        string $placeholder = '내용을 입력하세요.',
        int $height = 500
    ) {
        $this->name = $name;
        $this->id = $id ?: $this->makeId($name);
        $this->value = $value;
        $this->placeholder = $placeholder;
        $this->height = $height;
    }

    public function render(): View
    {
        return view('components.summernote');
    }

    public function fieldValue(): string
    {
        $value = old($this->name, $this->value);

        return is_string($value) ? $value : '';
    }

    private function makeId(string $name): string
    {
        $sanitized = Str::of($name)
            ->replace(['[', ']', '.'], '_')
            ->trim('_')
            ->value();

        return $sanitized . '_' . Str::lower(Str::random(6));
    }
}