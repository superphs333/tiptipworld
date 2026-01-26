import { Link } from '@inertiajs/react';

// Simple pagination using Laravel link data.
const decodeLabel = (label) =>
    label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', '이전')
        .replace('Next', '다음');

export default function Pagination({ links = [] }) {
    if (!links.length || links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex flex-wrap items-center justify-center gap-2">
            {links.map((link) => {
                const label = decodeLabel(link.label);
                const className =
                    'inline-flex items-center rounded-md px-3 py-1.5 text-sm font-semibold transition ' +
                    (link.active
                        ? 'bg-slate-900 text-white'
                        : link.url
                          ? 'border border-slate-200 text-slate-700 hover:border-slate-300 hover:text-slate-900'
                          : 'cursor-not-allowed border border-slate-100 text-slate-300');

                return link.url ? (
                    <Link
                        key={label + link.url}
                        href={link.url}
                        className={className}
                        preserveScroll
                    >
                        {label}
                    </Link>
                ) : (
                    <span key={label} className={className}>
                        {label}
                    </span>
                );
            })}
        </nav>
    );
}
