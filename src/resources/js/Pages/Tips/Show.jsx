import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, usePage } from '@inertiajs/react';

// Detail page for a single tip.
export default function Show({ tip }) {
    const { auth, flash } = usePage().props;
    const tipData = tip?.data ?? tip;

    return (
        <PublicLayout>
            <Head title={tipData?.title ?? '팁 상세'} />

            <div className="mx-auto max-w-4xl space-y-6">
                {flash?.success && (
                    <div className="rounded-md border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="text-2xl font-semibold text-gray-900">
                            {tipData?.title}
                        </h3>
                        <p className="mt-2 text-sm text-gray-500">
                            {tipData?.user?.name} · {tipData?.created_at}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('tips.index')}
                            className="text-sm font-semibold text-gray-600 hover:text-gray-900"
                        >
                            목록으로
                        </Link>
                        {tipData?.can?.update && (
                            <Link
                                href={route('tips.edit', tipData.id)}
                                className="inline-flex items-center rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                            >
                                수정
                            </Link>
                        )}
                        {tipData?.can?.delete && (
                            <Link
                                href={route('tips.destroy', tipData.id)}
                                method="delete"
                                as="button"
                                type="button"
                                onClick={(event) => {
                                    if (!window.confirm('삭제할까요?')) {
                                        event.preventDefault();
                                    }
                                }}
                                className="inline-flex items-center rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:border-red-300 hover:text-red-700"
                            >
                                삭제
                            </Link>
                        )}
                        {!tipData?.can?.update &&
                            !tipData?.can?.delete &&
                            auth?.user && (
                            <Link
                                href={route('tips.create')}
                                className="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                            >
                                새 팁 작성
                            </Link>
                        )}
                        {!auth?.user && (
                            <Link
                                href={route('login')}
                                className="inline-flex items-center rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                            >
                                로그인하고 작성
                            </Link>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div className="whitespace-pre-line px-6 py-6 text-gray-800">
                        {tipData?.content}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
