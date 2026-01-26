import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import { Link, usePage } from '@inertiajs/react';

// Public layout for the tips board (shared nav + background).
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 via-slate-50 to-slate-100">
            <nav className="border-b border-slate-200 bg-white/90 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-8">
                        <Link href="/" className="flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-8 fill-current text-slate-800" />
                            <span className="text-sm font-semibold tracking-tight text-slate-900">
                                TipTipWorld
                            </span>
                        </Link>
                        <NavLink
                            href={route('tips.index')}
                            active={route().current('tips.*')}
                        >
                            팁 게시판
                        </NavLink>
                    </div>

                    <div className="flex items-center gap-3">
                        {auth?.user ? (
                            <>
                                <span className="hidden text-sm text-slate-600 sm:inline">
                                    {auth.user.name}
                                </span>
                                <Link
                                    href={route('dashboard')}
                                    className="rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900"
                                >
                                    내 공간
                                </Link>
                                <Link
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                    className="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                >
                                    로그아웃
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900"
                                >
                                    로그인
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    회원가입
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </nav>

            <main className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
