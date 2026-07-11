import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { ConfirmDialog } from '@/components/cms/confirm-dialog';
import { IndexToolbar } from '@/components/cms/index-toolbar';
import { PageHeader } from '@/components/cms/page-header';
import { Pagination } from '@/components/cms/pagination';
import { StatusBadge } from '@/components/cms/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Page, Paginated } from '@/types/models';

export default function PagesIndex({
    items,
    filters,
}: {
    items: Paginated<Page>;
    filters: { search?: string; status?: string };
}) {
    return (
        <div className="space-y-4 p-4">
            <Head title="Pages" />
            <PageHeader
                title="Pages"
                description="Site pages and their content."
            >
                <Button asChild>
                    <Link href="/admin/pages/create">
                        <Plus className="size-4" /> New page
                    </Link>
                </Button>
            </PageHeader>

            <IndexToolbar path="/admin/pages" filters={filters} />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Updated</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.data.map((page) => (
                            <TableRow key={page.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={`/admin/pages/${page.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {page.title}
                                    </Link>
                                    {page.path && (
                                        <div className="text-xs font-normal text-muted-foreground">
                                            {page.path}
                                        </div>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={page.status} />
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDateTime(page.updated_at)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/pages/${page.id}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" size="icon">
                                                <Trash2 className="size-4" />
                                            </Button>
                                        }
                                        title={`Delete “${page.title}”?`}
                                        description="It will be moved to trash and can be restored by a super administrator."
                                        confirmLabel="Delete"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                `/admin/pages/${page.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                        {items.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No pages yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <Pagination meta={items} />
        </div>
    );
}
