<?php

namespace App\Http\Requests;

use App\Models\Tip;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 팁 글 삭제 요청 전용 FormRequest
 * 
 * [역할]
 * - 삭제 대상 Tip에 대한 접근 권한을 요청 단계에서 먼저 검사
 * - 삭제 요청에서 필요한 최소 입력값만 검증
 * - 컨트롤러가 raw input을 직접 해석하지 않도록 헬퍼 제공 
 */
class DestroyTipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tip = $this->route('tip');

        return $tip instanceof Tip
            && ($this->user()?->can('delete', $tip) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'submit_from' => ['nullable', 'in:admin,front'],
        ];
    }

    public function submitFrom(): string
    {
        $submitFrom = (string) $this->input('submit_from', 'admin');

        return $submitFrom !== '' ? $submitFrom : 'admin';
    }
}
