<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        <div class="space-y-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-slate-900">
                    <x-application-logo class="h-8 w-8 rounded-full ring-1 ring-slate-200" alt="팁팁월드 로고" width="32" height="32" />
                    <span class="text-base font-semibold tracking-tight">TipTipWorld</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">
                    팁 나누고 수다 떨고 같이 성장하는 공간.
                </p>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row">
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-slate-900">커뮤니티</h3>
                    <ul class="mt-3 space-y-1 text-sm text-slate-600">
                        <li><a href="#" class="hover:text-slate-900">커뮤 규칙</a></li>
                        <li><a href="#" class="hover:text-slate-900">공지</a></li>
                        <li><a href="#" class="hover:text-slate-900">운영팀</a></li>
                        <li><a href="#" class="hover:text-slate-900">배지</a></li>
                    </ul>
                </div>

                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-slate-900">도움말</h3>
                    <ul class="mt-3 space-y-1 text-sm text-slate-600">
                        <li><a href="#" class="hover:text-slate-900">FAQ</a></li>
                        <li><a href="#" class="hover:text-slate-900">문의하기</a></li>
                        <li><a href="#" class="hover:text-slate-900">피드백</a></li>
                        <li><a href="#" class="hover:text-slate-900">헬프센터</a></li>
                    </ul>
                </div>

                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-slate-900">약관/정책</h3>
                    <ul class="mt-3 space-y-1 text-sm text-slate-600">
                        <li><a href="#" class="hover:text-slate-900">이용약관</a></li>
                        <li><a href="#" class="hover:text-slate-900">개인정보</a></li>
                        <li><a href="#" class="hover:text-slate-900">쿠키</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-100 pt-6 text-sm text-slate-500">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} TipTipWorld. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="#" class="hover:text-slate-900">트위터</a>
                <a href="#" class="hover:text-slate-900">깃허브</a>
                <a href="#" class="hover:text-slate-900">이메일</a>
            </div>
            </div>
        </div>
    </div>
</footer>
