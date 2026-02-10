<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-slate-900">
                    <x-application-logo class="h-7 w-7 rounded-full ring-1 ring-slate-200" alt="팁팁월드 로고" width="28" height="28" />
                    <span class="text-sm font-semibold tracking-tight">TipTipWorld</span>
                </div>
                <p class="text-sm text-slate-600">팁 나누고 수다 떨고 같이 성장하는 공간.</p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm text-slate-600 sm:grid-cols-3 lg:gap-x-12">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-900">커뮤니티</h3>
                    <ul class="mt-1 space-y-0.5">
                        <li><a href="#" class="hover:text-slate-900">커뮤 규칙</a></li>
                        <li><a href="#" class="hover:text-slate-900">공지</a></li>
                        <li><a href="#" class="hover:text-slate-900">운영팀</a></li>
                        <li><a href="#" class="hover:text-slate-900">배지</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-900">도움말</h3>
                    <ul class="mt-1 space-y-0.5">
                        <li><a href="#" class="hover:text-slate-900">FAQ</a></li>
                        <li><a href="#" class="hover:text-slate-900">문의하기</a></li>
                        <li><a href="#" class="hover:text-slate-900">피드백</a></li>
                        <li><a href="#" class="hover:text-slate-900">헬프센터</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-900">약관/정책</h3>
                    <ul class="mt-1 space-y-0.5">
                        <li><a href="#" class="hover:text-slate-900">이용약관</a></li>
                        <li><a href="#" class="hover:text-slate-900">개인정보</a></li>
                        <li><a href="#" class="hover:text-slate-900">쿠키</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-5 border-t border-slate-100 pt-3 text-xs text-slate-500">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} TipTipWorld. All rights reserved.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="#" class="hover:text-slate-900">트위터</a>
                    <a href="#" class="hover:text-slate-900">깃허브</a>
                    <a href="#" class="hover:text-slate-900">이메일</a>
                </div>
            </div>
        </div>
    </div>
</footer>
