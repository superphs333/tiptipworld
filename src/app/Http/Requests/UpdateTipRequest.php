<?php

namespace App\Http\Requests;

use App\Models\Tip;

// 글 수정 요청 전용 검증/권한 클래스 
class UpdateTipRequest extends TipRequest
{
    public function authorize(): bool
    {
        $tip = $this->route('tip');

        return $tip instanceof Tip
            && ($this->user()?->can('update', $tip) ?? false);
    }
}
