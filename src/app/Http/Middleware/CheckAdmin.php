<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user(); // auth()->user()와 동일한 의미(인증 안 됐으면 null)

        // 로그인 되어있고 admin 역할이어야 통과
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
