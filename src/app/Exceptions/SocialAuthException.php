<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * 소셜 로그인 도메인에서 발생하는 "사용자에게 의미 있는 실패"를 표현하는 예외 클래스
 * 
 * [역할]
 * - OAuth 공급자 처리 중 발생하는 예욀르 한 종류로 묶음
 * - SocialAuthService가 실패 원인을 상황별로 던질 수 있게 한다. 
 * - 컨트롤러는 이 예외만 잡아서 로그인 화면으로 돌려보내고, 메시지를 표시하면 됨 
 * 
 * [사용흐름]
 * - SocialAuthService에서 상황별 static factory 메서드를 통해 예외를 생성
 * - SocialLoginController는 이 예외를 catch한 뒤, 로그인 페이지로 redirect 하면서 메세지를 전달
 * 
 * [설계 포인트]
 * - 예외를 new serlf(...)로 여기저기 흩뿌리지 안혹, 의미 있는 이름의 메서드로 생성
 */
class SocialAuthException extends RuntimeException
{
    /**
     * 소셜 로그인 자체를 계속 진행할 수 없는 일반 실패를 표현
     * 
     * @param string $message : 사용자에게 보여주거나 상위 계층에서 그대로 전달할 실패 메세지.
     * @param Throwable | null $prvious : 원래 발생한 예외를 보존, 디버깅 시 실제 근본 원인을 추적하기 쉽게 하려는 목적
     */
    public static function loginFailed(string $message, ?Throwable $previous = null): self
    {
        return new self($message, previous: $previous);
    }

    /**
     * provider 응답에는 성공처럼 보이지만, 서비스 정책상 필수인 이메일이 없는 경우를 표현
     */
    public static function missingEmail(string $message): self
    {
        return new self($message);
    }

    /**
     * 같은 이메일의 기존 계정이 이미 존재하므로 소셜 로그인을 자동 연결 없이 중단할 때 사용
     */
    public static function existingAccountRequiresLogin(): self
    {
        return new self('같은 이메일의 기존 계정이 있습니다. 기존 로그인 방식으로 로그인해 주세요.');
    }

    /**
     * 같은 이메일이 이미 다른 소셜 계정과 연결되어 있어서 자동 연동하면 안 되는 경우 표현
     * 
     * [방지문제]
     * - 이메일만 같다고 해서 무조건 다른 provider 계정에 덮어 연결해 버리면 계정탈취 or 잘못된 연동으로 이어짐 
     * => 기존 계정의 social_accounts 식별자와 현재 로그인 시도 정보가 다르면 예외를 던져 차단. 
     */
    public static function conflictingAccount(): self
    {
        return new self('이미 다른 소셜 계정과 연결된 이메일입니다. 기존 로그인 방식을 사용해 주세요.');
    }

    /**
     * 서비스가 지원하지 않은 provider 문자열이 들러온 경우 
     * 
     * @param string $provider : 현재 요청에 들어온 provider 값 
     * 
     * [용도]
     * - seetingsFor()같은 provider 분기 로직의 방버 코드 
     * - route/defulats, query string, 내부 호출 갑싱 예상 범위를 벗어났을 대 조기 실패
     * 
     */
    public static function unsupportedProvider(string $provider): self
    {
        return new self(sprintf('지원하지 않는 소셜 공급자입니다: %s', $provider));
    }
}
