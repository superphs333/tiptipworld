<?php

namespace App\Enums;

/**
 * 팁의 상태(status) 값을 표현하는 문자열 기반 enum
 * 
 * [역할]
 * - DB나 요청값으로 들어오는 status 문자열을 안전하게 enum으로 해석
 * - enum값을 화면 표시용 한글 라벨로 변환
 * - 상태별 UI 색상 톤(tone)을 반환
 * - 잘못된 값이나 빈 값이 들어와도 화면이 깨지지 않도록 기본값을 제공 
 * 
 *  [사용 예]
 * - 'draft'     -> '임시저장', 'gray'
 * - 'published' -> '게시',     'mint'
 * - 'archived'  -> '보관',     'gray'
 * - 'deleted'   -> '삭제',     'rose'
 */
enum TipStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Deleted = 'deleted';

    // 현재 enum값을 사람이 읽기 쉬운 한글 라벨로 변환 
    public function label(): string
    {
        return match ($this) {
            self::Draft => '임시저장',
            self::Published => '게시',
            self::Archived => '보관',
            self::Deleted => '삭제',
        };
    }

    // 현재 상태에 대응하는 UI 색상 톤 이름을 반환 
    public function tone(): string
    {
        return match ($this) {
            self::Published => 'mint',
            self::Deleted => 'rose',
            default => 'gray',
        };
    }

    /**
     * nullable 문자열을 enum 인스턴스로 변환 (안전한 파서)
     * 
     * [처리방식]
     * 1. null이어도 먼저 문자열로 캐스팅한다. 
     * 2. trim()으로 앞뒤 공백을 제거
     * 3. 빈 문자열이면 null 반환
     * 4. 값이 있으면 tryFrom()으로 enum 매핑 시도
     * 5. 정의되지 않은 값이면 예외 없이 null 반환
     */
    public static function fromValue(?string $value): ?self
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? self::tryFrom($normalized) : null;
    }

    /**
     * 원본 문자열에서 enum의 value를 안전하게 꺼냄
     * 
     * [반환규칙]
     * - 정상 값이면 해당 값 그대로 반환 
     * - 빈 값 / 잘못된 값 -> unkown 반환 
     * 
     * [용도]
     * 화면이나 data-attributes, CSS class key처럼 항상 문자열 하나는 필요할 때 쓰기 좋음 
     */
    public static function keyFor(?string $value): string
    {
        return self::fromValue($value)?->value ?? 'unknown';
    }

    /**
     * 원본 문자열을 사용자 표시용 텍스트로 바꿈
     * 
     * [반환 우선순위]
     * 1. enum으로 해석 가능하면 정식 한글 라벨 반환
     * 2. enum은 아니지만 공백 제외 문자열이 있으면 그 원문 반환
     * 3. 완전히 비어 있으면 기본값($default) 반환 
     */
    public static function labelFor(?string $value, string $default = '-'): string
    {
        $status = self::fromValue($value);

        if ($status instanceof self) {
            return $status->label();
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $default;
    }

    /**
     * 원본 문자열에서 상태 색상 톤을 안전하게 꺼냄
     * 
     * [반환규칙]
     * - 정상 enum이면 해당 상태의 tone 반환
     * - 빈 값 / 잘못된 값이면 중립값인 gray 반환 
     */
    public static function toneFor(?string $value): string
    {
        return self::fromValue($value)?->tone() ?? 'gray';
    }
}
