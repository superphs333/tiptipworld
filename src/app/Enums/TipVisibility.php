<?php

namespace App\Enums;

/**
 * 팁의 공개 범위(visibility)를 표현하는 문자열 기반 enum 
 * 
 * [역할]
 * - DB나 요청값으로 들어온 visibility 문자열을 안전하게 enum으로 변환
 * - enum 값을 화면 표시용 한글 라벨로 변환
 * - 공개 범위별 UI 색상 톤을 반환
 * - 현재 값이 외부에 공개 가능한 상태인지 boolean으로 판단
 * - 값이 비어 있거나 잘못되어도 화면에서 사용할 기본값을 제공한다. 
 * 
 * [의미]
 * - public : 누구나 볼 수 있는 공개 상태
 * - unlisted : 링크를 아는 사람 등 일부만 접근하는 제한 공개 상태
 * - private : 작성자 등 권한 있는 사용자만 보는 비공개 상태 
 */
enum TipVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';

    // 현재 enum 값을 사람이 읽기 쉬운 한글 라벨로 바꿈
    public function label(): string
    {
        return match ($this) {
            self::Public => '공개',
            self::Unlisted => '일부공개',
            self::Private => '비공개',
        };
    }

    // 현재 공개 범위에 대응하는 UI 색상 톤 이름을 반환 
    public function tone(): string
    {
        return match ($this) {
            self::Public => 'mint',
            self::Unlisted => 'rose',
            self::Private => 'gray',
        };
    }

    // 현재 공개 범위가 외부에 공개 가능한 상태인지 판단 (오직 public만 true)
    public function isPubliclyVisible(): bool
    {
        return $this === self::Public;
    }

    /**
     * nullable 문자열을 enum 인스턴스로 안전하게 변환
     * 
     * [처리 방식]
     * 1. null이어도 문자열로 캐스팅
     * 2. trim()으로 앞뒤 공백 제거
     * 3. 빈 문자열이면 null 반환
     * 4. 값이 있으면 tryFrom()으로 enum 매핑 시도
     * 5. 정의되지 않은 값이면 예외 없이 null 반환
     */
    public static function fromValue(?string $value): ?self
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? self::tryFrom($normalized) : null;
    }

    // 원본 문자열에서 enum의 실제 value를 안전하게 꺼낸다. (정상 값이면 해당 value, 빈 값/잘못된 값이면 public 반환)
    public static function keyFor(?string $value): string
    {
        return self::fromValue($value)?->value ?? 'public';
    }

    /**
     * 원본 문자열을 사용자 표시용 라벨로 바꿈
     * 
     * [반환 우선순위]
     * 1. enum으로 해석 가능하면 정식 한글 라벨 반환
     * 2. enum은 아니지만 문자열이 남아 있다면 그 원문 반환
     * 3. 완전히 비어 있으면 기본값($defaults) 반환
     */
    public static function labelFor(?string $value, string $default = '공개'): string
    {
        $visibility = self::fromValue($value);

        if ($visibility instanceof self) {
            return $visibility->label();
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $default;
    }

    /**
     * 원분 문자열에서 UI 색상 톤을 안전하게 꺼냄
     * 
     * [반환규칙]
     * - 정상 enum이면 해당 tone 반환
     * - 빈 값 / 잘못된 값이면 'mint' 반환
     */
    public static function toneFor(?string $value): string
    {
        return self::fromValue($value)?->tone() ?? 'mint';
    }
}
