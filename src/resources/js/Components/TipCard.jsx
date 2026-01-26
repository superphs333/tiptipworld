import { Link } from '@inertiajs/react';

// Small preview card for a tip item.
export default function TipCard({ tip }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <Link
                        href={route('tips.show', tip.id)}
                        className="text-lg font-semibold text-gray-900 hover:text-gray-700"
                    >
                        {tip.title}
                    </Link>
                    <p className="mt-2 text-sm text-gray-600">{tip.excerpt}</p>
                    <p className="mt-4 text-xs text-gray-500">
                        {tip.user?.name} · {tip.created_at}
                    </p>
                    {(tip.can?.update || tip.can?.delete) && (
                        <div className="mt-4 flex flex-wrap items-center gap-3 text-sm font-semibold">
                            {tip.can?.update && (
                                <Link
                                    href={route('tips.edit', tip.id)}
                                    className="text-slate-600 hover:text-slate-900"
                                >
                                    수정
                                </Link>
                            )}
                            {tip.can?.delete && (
                                <Link
                                    href={route('tips.destroy', tip.id)}
                                    method="delete"
                                    as="button"
                                    type="button"
                                    preserveScroll
                                    onClick={(event) => {
                                        if (!window.confirm('삭제할까요?')) {
                                            event.preventDefault();
                                        }
                                    }}
                                    className="text-red-600 hover:text-red-700"
                                >
                                    삭제
                                </Link>
                            )}
                        </div>
                    )}
                </div>
                <Link
                    href={route('tips.show', tip.id)}
                    className="inline-flex items-center text-sm font-semibold text-gray-700 hover:text-gray-900"
                >
                    자세히 보기 →
                </Link>
            </div>
        </div>
    );
}
