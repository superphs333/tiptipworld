<?php

namespace App\Services;

use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * [목적]
 * - 조회수(view_count)를 요청마다 무조건 올리지 않고, 같은 사용자가 같은 팁을 일정 시간(기본 24시간)내에 여러 번 봐도
 *   1회만 카운트 되도록 막는 서비스
 * 
 */
class TipViewCounterService
{
    // 특정 킵을 봤는지 저장할 쿠기 이름 접두사
    private const TIP_VIEW_COOKIE_PREFIX = 'tip_viewed_';
    // 시간
    private const TIP_VIEW_COOKIE_TTL_MINUTES = 1440; // 24h
    // 비로그인 사용자 
    private const GUEST_VIEWER_COOKIE = 'ttw_vid';
    // 게스트 식별 UUID 쿠리를 오래 유지(기본 365일)해서 게스트가 같은 기기/브라우저로 다시 왔을 때 동일인으로 인식 가능 
    private const GUEST_VIEWER_COOKIE_TTL_MINUTES = 525600; // 365d

    /**
     * 외부(컨트롤러)에서 호출
     *
     * @param Request $request
     * @param Tip $tip
     * @return boolean
     */
    public function increaseIfNeeded(Request $request, Tip $tip): bool
    {
        if (!$this->shouldIncreaseViewCount($request, (int) $tip->id)) {
            return false;
        }

        Tip::query()->whereKey($tip->id)->increment('view_count');
        $tip->view_count = (int) $tip->view_count + 1;

        return true;
    }

    // 요청에 조회수를 올려도 되는지 파악
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

    private function resolveGuestViewerId(Request $request): string
    {
        $guestId = $request->cookie(self::GUEST_VIEWER_COOKIE);

        if (!is_string($guestId) || !preg_match('/^[a-f0-9-]{36}$/i', $guestId)) {
            $guestId = (string) Str::uuid();
            $this->queueCookie(self::GUEST_VIEWER_COOKIE, $guestId, self::GUEST_VIEWER_COOKIE_TTL_MINUTES);
        }

        return $guestId;
    }

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
