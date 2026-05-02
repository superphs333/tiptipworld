<?php

namespace App\Services;

use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 팁 상세 조회 시 조회수(view_count)를 안전하게 증가시키는 서비스
 * 
 * [핵심목적]
 * - 상세 페이지 요청이 들어올 때마다 무조건 조회수를 올리지 않는다. 
 * - 같은 사용자(또는 같은 게스트 브라우저)가 같은 팁을 짧은 시간 안에 반복 조회해도 조회수는 1회만 증가하도록 막음
 * 
 * [동작방식]
 * - 식별
 *  - 로그인 사용자 -> user id 기준
 *  - 비로그인 사용자 -> 브라우저에 심어 둔 UUID 쿠키 기준으로 식별
 * - 이 사용자가 이 팁을 이미 봤는지 팁별 쿠키로 기록, 해당 기록이 없을 때만 DB의 view_count를 1증가시킴. 
 */
class TipViewCounterService
{
    // 개별 팁 조회 여부를 저장하는 쿠키 이름 prefix 
    private const TIP_VIEW_COOKIE_PREFIX = 'tip_viewed_';
    // 같은 팁에 대한 조회수 재증가를 막는 유지 시간 
    private const TIP_VIEW_COOKIE_TTL_MINUTES = 1440; // 24h
    // 비로그인 사용자를 식별하기 위한 게스트 전용 쿠키 ㅣ름 
    private const GUEST_VIEWER_COOKIE = 'ttw_vid';
    // 게스트 식별 UUID 쿠리를 오래 유지(기본 365일)해서 게스트가 같은 기기/브라우저로 다시 왔을 때 동일인으로 인식 가능 
    private const GUEST_VIEWER_COOKIE_TTL_MINUTES = 525600; // 365d

    /**
     * 외부(컨트롤러)에서 호출하는 조회수 증가 진입점
     * 
     * [처리흐름]
     * 1. 현재 요청이 조회수 증가 대상인지 확인
     * 2. 이미 본 기록이 있으면 아무 것도 하지 않고 false 반환
     * 3. 처음 보는 요청이면 DB의 view_count를 1증가시키고 true 반환 
     *
     * @param Request $request
     * @param Tip $tip
     * @return boolean
     */
    public function increaseIfNeeded(Request $request, Tip $tip): bool
    {
        // 같은 사용자/게스트가 이미 최근에 본 팁이면 증가시키지 않음.
        if (!$this->shouldIncreaseViewCount($request, (int) $tip->id)) {
            return false;
        }

        // db에서 원자적으로 1증가
        Tip::query()->whereKey($tip->id)->increment('view_count');

        // 메모리에 들고있는 모델 값도 함께 맞춰줌 
        $tip->view_count = (int) $tip->view_count + 1;

        return true;
    }

    /**
     * 현재 요청이 조회수 증가 대상인지 판단
     * 
     * [판정기준]
     * - 식별
     *  - 로그인 사용자 => u_{id}
     *  - 게스트 => g_{uuid}
     * - 팁 id + 사용자 식별값을 합친 쿠키가 이미 존재하면, 이미 본 요청으로 간주하고 조회수를 올리지 않음.    
     */
    private function shouldIncreaseViewCount(Request $request, int $tipId): bool
    {
        // 식별키
        $viewerKey = Auth::check()
            ? 'u_' . Auth::id()
            : 'g_' . $this->resolveGuestViewerId($request);

        $viewCookieName = self::TIP_VIEW_COOKIE_PREFIX . $tipId . '_' . $viewerKey;

        // 이미 쿠키가 있음 => 이미 본 것으로 간주 => 조회수 증가 금지
        if ($request->cookie($viewCookieName) !== null) {
            return false;
        }

        $this->queueCookie($viewCookieName, '1', self::TIP_VIEW_COOKIE_TTL_MINUTES);

        return true;
    }
    /**
     * 게스트 사용자를 식별할 UUID 반환
     * 
     * [동작]
     * - 유효한 게스트 식별 쿠키가 있으면 그대로 사용
     * - 없거나 형식이 잘못되었으면 새 UUID를 생성하고 쿠키로 저장
     * 
     * 
     * 
     */
    private function resolveGuestViewerId(Request $request): string
    {
        $guestId = $request->cookie(self::GUEST_VIEWER_COOKIE);

        if (!is_string($guestId) || !preg_match('/^[a-f0-9-]{36}$/i', $guestId)) {
            $guestId = (string) Str::uuid();
            $this->queueCookie(self::GUEST_VIEWER_COOKIE, $guestId, self::GUEST_VIEWER_COOKIE_TTL_MINUTES);
        }

        return $guestId;
    }

    /**
     * 공통 쿠키 등록 헬퍼 (조회수 관련 쿠키를 일관된 정책으로 저장하기 위함)
     * : 라라벨의 cookie()->queue()를 사용해 응답 시점에 쿠키가 실리도록 예약
     * 
     * [옵션]
     * - path : /
     * - domain : session 설정값 사용
     * - secure : session.secure 또는 production 환경 여부 반영
     * - httpOnly / samSite : session 설정을 최대한 따름 
     */
    private function queueCookie(string $name, string $value, int $minutes): void
    {
        $secure = config('session.secure') ?? app()->environment('production');

        cookie()->queue(cookie(
            $name,
            $value,
            $minutes,
            '/',
            config('session.domain'),
            (bool) $secure,
            (bool) config('session.http_only', true),
            false,
            config('session.same_site', 'lax')
        ));
    }
}
