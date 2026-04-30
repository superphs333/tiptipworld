<?php

namespace App\Services;

use App\Models\Tip;

/**
 * TipWriteService의 처리 결과를 컨트롤러로 전달하기 위한 DTO 
 * 
 * [역할]
 * - 글 작성/수정이 끝난 뒤 필요한 결과값을 하나의 객체로 묶어서 반환
 * - 컨트롤러가 배열 키를 해석하지 않고 명확한 속성명으로 결과를 사용하게 함
 */
final readonly class TipWriteResult
{
    public function __construct(
        public Tip $tip, // 저장 또는 수정이 완료된 Tip 모델
        public ?string $warningMessage = null,
            // 저장은 성공했지만 사용자에게 보여줄 경고가 있을 때 사용
    ) {
    }
}
