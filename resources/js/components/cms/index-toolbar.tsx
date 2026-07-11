import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const STATUSES = ['draft', 'published', 'archived'];

export function IndexToolbar({
    path,
    filters,
    withStatus = true,
    placeholder = 'Search…',
}: {
    path: string;
    filters: { search?: string; status?: string };
    withStatus?: boolean;
    placeholder?: string;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const first = useRef(true);

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const t = setTimeout(() => {
            router.get(path, cleaned({ search, status: filters.status }), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const setStatus = (status: string) => {
        router.get(path, cleaned({ search, status }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <Input
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder={placeholder}
                className="max-w-xs"
            />
            {withStatus && (
                <Select
                    value={filters.status || 'all'}
                    onValueChange={(v) => setStatus(v === 'all' ? '' : v)}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        {STATUSES.map((s) => (
                            <SelectItem
                                key={s}
                                value={s}
                                className="capitalize"
                            >
                                {s}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}

function cleaned(obj: Record<string, string | undefined>) {
    return Object.fromEntries(Object.entries(obj).filter(([, v]) => v));
}
