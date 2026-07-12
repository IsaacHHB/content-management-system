import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/models';

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace(/<[^>]*>/g, '');
}

export function Pagination<T>({ meta }: { meta: Paginated<T> }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
            <span className="text-muted-foreground">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
            </span>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, i) => (
                    <Link
                        key={i}
                        href={link.url ?? '#'}
                        preserveScroll
                        preserveState
                        className={cn(
                            'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2',
                            link.active &&
                                'border-primary bg-primary text-primary-foreground',
                            !link.url && 'pointer-events-none opacity-50',
                        )}
                    >
                        {paginationLabel(link.label)}
                    </Link>
                ))}
            </div>
        </nav>
    );
}
