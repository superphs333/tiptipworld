import Pagination from '@/Components/Pagination';
import TipCard from '@/Components/TipCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, usePage } from '@inertiajs/react';

// Public list page with pagination.
export default function Index({ tips = { data: [], links: [], meta: null } }) {
    const { auth, flash } = usePage().props;
    const list = tips?.data ?? [];
    const meta = tips?.meta;

    return (
        <PublicLayout>
            <Head title="팁 게시판" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">
                            팁 공유 공간
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            경험을 나누고 팀의 지식을 쌓아보세요.
                        </p>
                    </div>
                    {auth?.user ? (
                        <Link
                            href={route('tips.create')}
                            className="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                        >
                            팁 작성
                        </Link>
                    ) : (
                        <Link
                            href={route('login')}
                            className="inline-flex items-center justify-center rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                        >
                            로그인하고 작성
                        </Link>
                    )}
                </div>

                {flash?.success && (
                    <div className="rounded-md border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                {meta && meta.total > 0 && (
                    <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
                        <span>
                            전체 {meta.total}개 · {meta.from}-{meta.to} 표시
                        </span>
                        <span>
                            {meta.current_page}/{meta.last_page} 페이지
                        </span>
                    </div>
                )}

                <div className="space-y-4">
                    {list.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 shadow-sm">
                            아직 등록된 팁이 없습니다. 첫 번째 팁을
                            작성해보세요.
                        </div>
                    ) : (
                        list.map((tip) => (
                            <TipCard key={tip.id} tip={tip} />
                        ))
                    )}
                </div>

                <Pagination links={tips?.links ?? []} />
            </div>
        </PublicLayout>
    );
}
